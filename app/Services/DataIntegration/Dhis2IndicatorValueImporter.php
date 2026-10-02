<?php

namespace App\Services\DataIntegration;

use App\Models\DataIntegrationConnection;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Support\ApprovalWorkflow;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Dhis2IndicatorValueImporter
{
    /**
     * Import DHIS2 analytics rows into fact_data_indicators as pending values.
     *
     * @return array{created: int, updated: int, skipped: int, rows: array<int, array<string, mixed>>, message: string}
     */
    public function import(DataIntegrationConnection $connection): array
    {
        $this->ensureSupportedConnection($connection);

        $scope = $this->dhis2Scope($connection);
        $rows = $this->fetchAnalyticsRows($connection, $scope);
        $references = $this->resolveReferences($connection, $scope);

        $result = DB::connection('warehouse')->transaction(function () use ($rows, $references, $scope, $connection): array {
            $result = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'rows' => [],
            ];

            foreach ($rows as $row) {
                $mappedRow = $this->mapAnalyticsRow($row, $scope, $references, $connection);

                if ($mappedRow === null) {
                    $result['skipped']++;

                    continue;
                }

                $record = HealthIndicatorValue::query()
                    ->where('indicator_id', $mappedRow['indicator_id'])
                    ->where('location_id', $mappedRow['location_id'])
                    ->where('period', $mappedRow['period'])
                    ->where('categoryoption_id', $mappedRow['categoryoption_id'])
                    ->where('datasource_id', $mappedRow['datasource_id'])
                    ->where('measuremethod_id', $mappedRow['measuremethod_id'])
                    ->first();

                $wasExisting = $record instanceof HealthIndicatorValue;
                $record ??= new HealthIndicatorValue;

                $record->fill([
                    ...$mappedRow,
                    'comment' => ApprovalWorkflow::STATUS_PENDING,
                    'approval_status' => ApprovalWorkflow::STATUS_PENDING,
                    'approved_by' => null,
                    'approved_at' => null,
                    'user_id' => $connection->user_id ?? auth()->id() ?? 1,
                    'string_value' => null,
                    'priority' => false,
                ]);

                $record->save();

                $result[$wasExisting ? 'updated' : 'created']++;
                $result['rows'][] = [
                    'fact_id' => $record->fact_id,
                    'indicator' => $record->indicator?->afrocode,
                    'period' => $record->period,
                    'value_received' => $record->value_received,
                    'status' => ApprovalWorkflow::status($record),
                ];
            }

            return $result;
        });

        $message = __('aho.data_integration.messages.dhis2_imported', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
        ]);

        $connection->forceFill([
            'last_synced_at' => now(),
            'last_test_status' => 'ready',
            'last_test_message' => $message,
        ])->save();

        return [
            ...$result,
            'message' => $message,
        ];
    }

    private function ensureSupportedConnection(DataIntegrationConnection $connection): void
    {
        if (
            $connection->provider !== DataIntegrationConnection::PROVIDER_DHIS2
            || $connection->integration_method !== DataIntegrationConnection::METHOD_API
        ) {
            throw new \InvalidArgumentException('This importer only supports DHIS2 API connections.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dhis2Scope(DataIntegrationConnection $connection): array
    {
        $scope = $connection->data_scope ?? [];
        $dhis2 = $scope['dhis2'] ?? $scope;

        $mappings = $dhis2['mappings'] ?? [];
        $periods = array_values(array_filter((array) ($dhis2['periods'] ?? [$dhis2['period'] ?? null])));
        $organisationUnit = $dhis2['organisation_unit'] ?? $dhis2['org_unit'] ?? $dhis2['ou'] ?? null;

        if ($mappings === [] || blank($organisationUnit) || $periods === []) {
            throw new \InvalidArgumentException('The DHIS2 data scope must define mappings, organisation_unit and periods.');
        }

        return [
            ...$dhis2,
            'mappings' => $mappings,
            'periods' => $periods,
            'organisation_unit' => (string) $organisationUnit,
        ];
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<int, array<int, mixed>>
     */
    private function fetchAnalyticsRows(DataIntegrationConnection $connection, array $scope): array
    {
        $dimensionIds = implode(';', array_keys($scope['mappings']));
        $periods = implode(';', $scope['periods']);
        $organisationUnit = $scope['organisation_unit'];

        $query = implode('&', [
            'dimension='.rawurlencode("dx:{$dimensionIds}"),
            'dimension='.rawurlencode("pe:{$periods}"),
            'filter='.rawurlencode("ou:{$organisationUnit}"),
            'displayProperty=NAME',
            'skipMeta=false',
        ]);

        $response = $this->httpClient($connection)->get($this->dhis2Endpoint($connection, 'analytics.json').'?'.$query);

        if (! $response->successful()) {
            throw new \RuntimeException('DHIS2 analytics returned HTTP '.$response->status().'.');
        }

        $rows = $response->json('rows');

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, mixed>  $scope
     * @param  array<string, int>  $references
     * @return array<string, mixed>|null
     */
    private function mapAnalyticsRow(array $row, array $scope, array $references, DataIntegrationConnection $connection): ?array
    {
        [$dimensionId, $period, $value] = [$row[0] ?? null, $row[1] ?? null, $row[2] ?? null];

        if (! is_string($dimensionId) || ! isset($scope['mappings'][$dimensionId]) || ! is_numeric($value)) {
            return null;
        }

        $mapping = $scope['mappings'][$dimensionId];
        $year = $this->periodYear((string) $period);

        if ($year === null) {
            return null;
        }

        $indicator = $this->resolveIndicator($mapping);
        $measureMethod = $this->resolveMeasureMethod($mapping);

        return [
            'indicator_id' => $indicator->indicator_id,
            'location_id' => (int) ($scope['location_id'] ?? $connection->location_id),
            'start_period' => $year,
            'end_period' => $year,
            'period' => (string) $year,
            'categoryoption_id' => $references['categoryoption_id'],
            'datasource_id' => $references['datasource_id'],
            'measuremethod_id' => $measureMethod->measuremethod_id,
            'value_received' => $this->transformValue((float) $value, $mapping),
            'numerator_value' => null,
            'denominator_value' => null,
            'min_value' => null,
            'max_value' => null,
            'target_value' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<string, int>
     */
    private function resolveReferences(DataIntegrationConnection $connection, array $scope): array
    {
        $categoryOption = $this->resolveCategoryOption($scope);
        $dataSource = $this->resolveDataSource($scope);

        if (blank($connection->location_id) && blank($scope['location_id'] ?? null)) {
            throw new \InvalidArgumentException('The DHIS2 connection must be attached to a local country.');
        }

        return [
            'categoryoption_id' => $categoryOption->categoryoption_id,
            'datasource_id' => $dataSource->datasource_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function resolveIndicator(array $mapping): Indicator
    {
        $indicator = Indicator::query()
            ->when($mapping['indicator_id'] ?? null, fn ($query, mixed $id) => $query->where('indicator_id', $id))
            ->when($mapping['indicator_afrocode'] ?? $mapping['indicator_code'] ?? null, fn ($query, mixed $code) => $query->orWhere('afrocode', $code))
            ->first();

        if (! $indicator) {
            throw new \InvalidArgumentException('Mapped local indicator was not found.');
        }

        return $indicator;
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function resolveMeasureMethod(array $mapping): MeasureMethod
    {
        $measureMethod = MeasureMethod::query()
            ->when($mapping['measuremethod_id'] ?? null, fn ($query, mixed $id) => $query->where('measuremethod_id', $id))
            ->when($mapping['measuremethod_code'] ?? null, fn ($query, mixed $code) => $query->orWhere('code', $code))
            ->first();

        if (! $measureMethod) {
            throw new \InvalidArgumentException('Mapped local measure method was not found.');
        }

        return $measureMethod;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function resolveCategoryOption(array $scope): IndicatorCategory
    {
        $categoryOption = IndicatorCategory::query()
            ->when($scope['categoryoption_id'] ?? null, fn ($query, mixed $id) => $query->where('categoryoption_id', $id))
            ->when($scope['categoryoption_code'] ?? 'ADC0029', fn ($query, mixed $code) => $query->orWhere('code', $code))
            ->first();

        if (! $categoryOption) {
            throw new \InvalidArgumentException('Default category option was not found.');
        }

        return $categoryOption;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function resolveDataSource(array $scope): DataSource
    {
        $dataSource = DataSource::query()
            ->when($scope['datasource_id'] ?? null, fn ($query, mixed $id) => $query->where('datasource_id', $id))
            ->when($scope['datasource_code'] ?? 'ADS0026', fn ($query, mixed $code) => $query->orWhere('code', $code))
            ->first();

        if (! $dataSource) {
            throw new \InvalidArgumentException('Default data source was not found.');
        }

        return $dataSource;
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function transformValue(float $value, array $mapping): float
    {
        return match ($mapping['transform'] ?? null) {
            'divide_by_1000' => round($value / 1000, 2),
            default => round($value, 2),
        };
    }

    private function periodYear(string $period): ?int
    {
        if (preg_match('/^(\d{4})/', $period, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function httpClient(DataIntegrationConnection $connection): PendingRequest
    {
        $request = Http::timeout($connection->connection_timeout ?: 20)->acceptJson();

        return match ($connection->auth_type) {
            'basic' => $request->withBasicAuth((string) $connection->username, (string) $connection->password),
            'bearer' => $request->withToken((string) $connection->api_token),
            'api_key' => filled($connection->api_key_name)
                ? $request->withHeaders([(string) $connection->api_key_name => (string) $connection->api_key_value])
                : $request,
            default => $request,
        };
    }

    private function dhis2Endpoint(DataIntegrationConnection $connection, string $path): string
    {
        $baseUrl = rtrim((string) $connection->api_url, '/');
        $apiBaseUrl = str_ends_with($baseUrl, '/api') ? $baseUrl : $baseUrl.'/api';

        return $apiBaseUrl.'/'.ltrim($path, '/');
    }
}
