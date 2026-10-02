<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\DataIntegrationConnection;
use App\Models\DataIntegrationFieldMapping;
use App\Models\User;
use App\Services\DataIntegration\Dhis2IndicatorValueImporter;
use Illuminate\Console\Command;

class ImportDhis2IndicatorValues extends Command
{
    protected $signature = 'data-integration:import-dhis2-indicators
        {connection? : Data integration connection id or name}
        {--demo : Create or update the public Sierra Leone DHIS2 demo connection before importing}';

    protected $description = 'Import DHIS2 analytics values into the indicator values table as pending records.';

    public function handle(Dhis2IndicatorValueImporter $importer): int
    {
        $connection = $this->option('demo')
            ? $this->createOrUpdateDemoConnection()
            : $this->resolveConnection((string) $this->argument('connection'));

        if (! $connection instanceof DataIntegrationConnection) {
            $this->error('Provide a DHIS2 connection id/name or use --demo.');

            return self::FAILURE;
        }

        $validation = $connection->validateConfiguration();

        if (! $validation['ok']) {
            $this->warn($validation['message']);
        }

        $result = $importer->import($connection);

        $this->info($result['message']);
        $this->table(
            ['Fact ID', 'Indicator', 'Period', 'Value', 'Status'],
            collect($result['rows'])
                ->map(fn (array $row): array => [
                    $row['fact_id'],
                    $row['indicator'],
                    $row['period'],
                    $row['value_received'],
                    $row['status'],
                ])
                ->all(),
        );

        return self::SUCCESS;
    }

    private function resolveConnection(string $identifier): ?DataIntegrationConnection
    {
        if (blank($identifier)) {
            return null;
        }

        return DataIntegrationConnection::query()
            ->where('id', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }

    private function createOrUpdateDemoConnection(): DataIntegrationConnection
    {
        $country = Country::query()
            ->where('iso_alpha', 'SL')
            ->firstOrFail();

        $userId = User::query()
            ->where('is_super_admin', true)
            ->value('id') ?? User::query()->value('id');

        $connection = DataIntegrationConnection::updateOrCreate(
            ['name' => 'DHIS2 Demo Sierra Leone'],
            [
                'user_id' => $userId,
                'location_id' => $country->location_id,
                'provider' => DataIntegrationConnection::PROVIDER_DHIS2,
                'integration_method' => DataIntegrationConnection::METHOD_API,
                'status' => DataIntegrationConnection::STATUS_ACTIVE,
                'sync_frequency' => 'manual',
                'api_url' => config('services.dhis2_demo.api_url'),
                'auth_type' => config('services.dhis2_demo.auth_type'),
                'username' => config('services.dhis2_demo.username'),
                'password' => config('services.dhis2_demo.password'),
                'connection_timeout' => 30,
                'data_scope' => [
                    'dhis2' => [
                        'organisation_unit' => config('services.dhis2_demo.organisation_unit'),
                        'organisation_unit_name' => config('services.dhis2_demo.organisation_unit_name'),
                        'periods' => ['2022', '2023', '2024'],
                        'location_id' => $country->location_id,
                        'categoryoption_code' => config('services.dhis2_demo.categoryoption_code'),
                        'datasource_code' => config('services.dhis2_demo.datasource_code'),
                        'mappings' => [
                            'FnYCr2EAzWS' => [
                                'source_name' => 'BCG Coverage <1y',
                                'indicator_afrocode' => 'AFR0197',
                                'measuremethod_code' => 'AMM0001',
                            ],
                            'WUg3MYWQ7pt' => [
                                'source_name' => 'Total Population',
                                'indicator_afrocode' => 'AFR0004',
                                'measuremethod_code' => 'AMM0018',
                                'transform' => 'divide_by_1000',
                            ],
                        ],
                    ],
                ],
                'notes' => 'Public DHIS2 demo connection used to validate API import, field mapping and pending approval workflow.',
            ],
        );

        $this->syncDemoFieldMappings($connection);

        return $connection->refresh();
    }

    private function syncDemoFieldMappings(DataIntegrationConnection $connection): void
    {
        $mappings = [
            ['local_field' => 'indicator_id', 'external_field' => 'dx', 'field_type' => 'lookup', 'is_required' => true, 'reference_match' => 'id'],
            ['local_field' => 'location_id', 'external_field' => 'ou', 'field_type' => 'lookup', 'is_required' => true, 'reference_match' => 'id'],
            ['local_field' => 'period', 'external_field' => 'pe', 'field_type' => 'direct', 'is_required' => true],
            ['local_field' => 'start_period', 'external_field' => 'pe', 'field_type' => 'computed', 'is_required' => true],
            ['local_field' => 'end_period', 'external_field' => 'pe', 'field_type' => 'computed', 'is_required' => true],
            ['local_field' => 'value_received', 'external_field' => 'value', 'field_type' => 'direct', 'is_required' => true],
            ['local_field' => 'categoryoption_id', 'external_field' => 'categoryOptionCombo', 'field_type' => 'computed', 'is_required' => true, 'default_value' => 'ADC0029'],
            ['local_field' => 'datasource_id', 'external_field' => 'DHIS2 source', 'field_type' => 'computed', 'is_required' => true, 'default_value' => 'ADS0026'],
            ['local_field' => 'measuremethod_id', 'external_field' => 'dx', 'field_type' => 'computed', 'is_required' => true],
            ['local_field' => 'comment', 'external_field' => 'approval_status', 'field_type' => 'computed', 'is_required' => true, 'default_value' => 'pending'],
        ];

        foreach ($mappings as $sortOrder => $mapping) {
            DataIntegrationFieldMapping::updateOrCreate(
                [
                    'data_integration_connection_id' => $connection->id,
                    'local_field' => $mapping['local_field'],
                ],
                [
                    'external_field' => $mapping['external_field'],
                    'field_type' => $mapping['field_type'],
                    'is_required' => $mapping['is_required'],
                    'transformation_config' => array_filter([
                        'reference_match' => $mapping['reference_match'] ?? null,
                        'default_value' => $mapping['default_value'] ?? null,
                    ], fn (mixed $value): bool => filled($value)),
                    'notes' => $mapping['notes'] ?? null,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
