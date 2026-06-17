<?php

namespace App\Support\DataIntegration;

use App\Models\DataIntegrationConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

class ExternalFieldDetector
{
    /**
     * Read the saved connection and return the source fields that can be mapped.
     *
     * API connections are sampled from the configured URL. DHIS2 connections also
     * receive a known data-value fallback because a DHIS2 server can be valid even
     * when the sampled metadata endpoints currently return no rows.
     *
     * @return Collection<int, string>
     */
    public static function detect(DataIntegrationConnection $connection): Collection
    {
        $fields = match ($connection->integration_method) {
            DataIntegrationConnection::METHOD_API => self::detectApiFields($connection),
            DataIntegrationConnection::METHOD_DIRECT => self::detectDatabaseFields($connection),
            default => collect(),
        };

        return $fields
            ->map(fn (string $field): string => self::normalizeFieldPath($field))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private static function detectApiFields(DataIntegrationConnection $connection): Collection
    {
        if (blank($connection->api_url)) {
            return collect();
        }

        if ($connection->provider === DataIntegrationConnection::PROVIDER_DHIS2) {
            return self::detectDhis2Fields($connection);
        }

        $payload = self::requestJson($connection, (string) $connection->api_url);

        return self::extractFieldPaths($payload);
    }

    /**
     * @return Collection<int, string>
     */
    private static function detectDhis2Fields(DataIntegrationConnection $connection): Collection
    {
        $baseUrl = self::dhis2ApiBaseUrl($connection);

        $fields = collect(self::knownDhis2DataValueFields());

        foreach (self::dhis2SampleEndpoints() as $endpoint) {
            try {
                $payload = self::requestJson($connection, $baseUrl.'/'.ltrim($endpoint, '/'));
                $fields = $fields->merge(self::extractFieldPaths($payload));
            } catch (\Throwable) {
                // Keep trying the other endpoints, then fall back to known DHIS2 fields.
            }
        }

        return $fields;
    }

    /**
     * @return array<int, string>
     */
    private static function dhis2SampleEndpoints(): array
    {
        return [
            'dataValueSets?fields=dataValues[dataElement,period,orgUnit,categoryOptionCombo,attributeOptionCombo,value,comment,storedBy,created,lastUpdated]&paging=false&pageSize=1',
            'dataElements?fields=id,code,name,shortName,valueType,aggregationType&paging=false&pageSize=1',
            'organisationUnits?fields=id,code,name,shortName,level,parent[id,code,name]&paging=false&pageSize=1',
            'dataSets?fields=id,code,name,periodType,dataSetElements[dataElement[id,code,name]]&paging=false&pageSize=1',
            'schemas/dataValueSet',
            'schemas/dataElement',
            'schemas/organisationUnit',
        ];
    }

    private static function dhis2ApiBaseUrl(DataIntegrationConnection $connection): string
    {
        $baseUrl = rtrim((string) $connection->api_url, '/');

        return str_ends_with($baseUrl, '/api') ? $baseUrl : $baseUrl.'/api';
    }

    /**
     * @return array<int, string>
     */
    private static function knownDhis2DataValueFields(): array
    {
        return [
            'dataElement',
            'dataElement.code',
            'dataElement.id',
            'dataElement.name',
            'period',
            'orgUnit',
            'orgUnit.code',
            'orgUnit.id',
            'orgUnit.name',
            'categoryOptionCombo',
            'attributeOptionCombo',
            'value',
            'comment',
            'storedBy',
            'created',
            'lastUpdated',
            'followUp',
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private static function detectDatabaseFields(DataIntegrationConnection $connection): Collection
    {
        $tableName = self::sourceTableName($connection);

        if (blank($tableName) || blank($connection->server_name) || blank($connection->database_driver) || blank($connection->database_name)) {
            return collect();
        }

        $connectionName = 'external_data_integration_'.$connection->getKey();

        Config::set("database.connections.{$connectionName}", self::databaseConnectionConfig($connection));
        DB::purge($connectionName);

        try {
            if (! DatabaseSchema::connection($connectionName)->hasTable($tableName)) {
                return collect();
            }

            return collect(DatabaseSchema::connection($connectionName)->getColumnListing($tableName));
        } finally {
            DB::disconnect($connectionName);
            DB::purge($connectionName);
        }
    }

    private static function sourceTableName(DataIntegrationConnection $connection): ?string
    {
        $scope = $connection->data_scope ?? [];

        foreach (['source_table', 'table', 'table_name', 'view', 'view_name', 'dataset'] as $key) {
            if (filled($scope[$key] ?? null)) {
                return (string) $scope[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function databaseConnectionConfig(DataIntegrationConnection $connection): array
    {
        $driver = (string) $connection->database_driver;

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => (string) $connection->database_name,
                'prefix' => '',
            ];
        }

        return [
            'driver' => $driver,
            'host' => (string) $connection->server_name,
            'port' => $connection->port,
            'database' => (string) $connection->database_name,
            'username' => (string) $connection->username,
            'password' => (string) $connection->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
        ];
    }

    /**
     * @return array<string, mixed>|array<int, mixed>
     */
    private static function requestJson(DataIntegrationConnection $connection, string $url): array
    {
        $response = self::httpClient($connection)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status().' returned by '.$url);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private static function httpClient(DataIntegrationConnection $connection): PendingRequest
    {
        $request = Http::timeout(20)->acceptJson();

        return match ($connection->auth_type) {
            'basic' => $request->withBasicAuth((string) $connection->username, (string) $connection->password),
            'bearer' => $request->withToken((string) $connection->api_token),
            'api_key' => filled($connection->api_key_name)
                ? $request->withHeaders([(string) $connection->api_key_name => (string) $connection->api_key_value])
                : $request,
            default => $request,
        };
    }

    /**
     * @param  mixed  $payload
     * @return Collection<int, string>
     */
    private static function extractFieldPaths(mixed $payload, ?string $prefix = null, int $depth = 0): Collection
    {
        if ($depth > 6 || ! is_array($payload)) {
            return collect();
        }

        if (array_is_list($payload)) {
            return collect($payload)
                ->take(10)
                ->flatMap(fn (mixed $item): Collection => self::extractFieldPaths($item, $prefix, $depth + 1));
        }

        return collect($payload)
            ->flatMap(function (mixed $value, string|int $key) use ($prefix, $depth): Collection {
                $key = (string) $key;

                if (str_starts_with($key, '_')) {
                    return collect();
                }

                $path = filled($prefix) ? "{$prefix}.{$key}" : $key;

                if (is_array($value)) {
                    return collect([$path])->merge(self::extractFieldPaths($value, $path, $depth + 1));
                }

                return collect([$path]);
            })
            ->take(250);
    }

    private static function normalizeFieldPath(string $field): string
    {
        return collect(explode('.', $field))
            ->reject(fn (string $segment): bool => $segment === '' || is_numeric($segment))
            ->implode('.');
    }
}
