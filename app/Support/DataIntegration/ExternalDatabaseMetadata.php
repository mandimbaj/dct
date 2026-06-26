<?php

namespace App\Support\DataIntegration;

use App\Models\DataIntegrationConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ExternalDatabaseMetadata
{
    /** @var array<string, array<string, string>> */
    private static array $databaseCache = [];

    /** @var array<string, array<string, string>> */
    private static array $relationCache = [];

    /**
     * @return array<string, string>
     */
    public static function databases(DataIntegrationConnection $connection): array
    {
        if ($connection->database_driver === 'sqlite') {
            $database = trim((string) $connection->database_name);

            return filled($database) ? [$database => basename($database)] : [];
        }

        $cacheKey = self::cacheKey($connection, 'databases');

        if (isset(self::$databaseCache[$cacheKey])) {
            return self::$databaseCache[$cacheKey];
        }

        $probe = clone $connection;
        $probe->database_name = match ($connection->database_driver) {
            'mysql' => 'information_schema',
            'pgsql' => filled($connection->database_name) ? $connection->database_name : 'postgres',
            'sqlsrv' => 'master',
            default => $connection->database_name,
        };

        $connectionName = ExternalDatabaseConnection::configure($probe);

        try {
            $rows = match ($connection->database_driver) {
                'mysql' => DB::connection($connectionName)->select(
                    "SELECT SCHEMA_NAME AS name FROM information_schema.SCHEMATA
                     WHERE SCHEMA_NAME NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
                     ORDER BY SCHEMA_NAME",
                ),
                'pgsql' => DB::connection($connectionName)->select(
                    'SELECT datname AS name FROM pg_database WHERE datallowconn = true AND datistemplate = false ORDER BY datname',
                ),
                'sqlsrv' => DB::connection($connectionName)->select(
                    'SELECT name FROM sys.databases WHERE state = 0 AND database_id > 4 ORDER BY name',
                ),
                default => filled($connection->database_name)
                    ? [(object) ['name' => $connection->database_name]]
                    : [],
            };

            return self::$databaseCache[$cacheKey] = collect($rows)
                ->map(fn (object|array $row): string => trim((string) self::value($row, 'name')))
                ->filter()
                ->mapWithKeys(fn (string $database): array => [$database => $database])
                ->all();
        } catch (Throwable $e) {
            throw ExternalDatabaseConnection::friendlyFailure($e);
        } finally {
            ExternalDatabaseConnection::disconnect($connectionName);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function relations(DataIntegrationConnection $connection): array
    {
        if (blank($connection->database_name)) {
            return [];
        }

        $cacheKey = self::cacheKey($connection, 'relations');

        if (isset(self::$relationCache[$cacheKey])) {
            return self::$relationCache[$cacheKey];
        }

        $connectionName = ExternalDatabaseConnection::configure($connection);

        try {
            $rows = match ($connection->database_driver) {
                'mysql' => DB::connection($connectionName)->select(
                    "SELECT TABLE_NAME AS name, TABLE_TYPE AS type
                     FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE IN ('BASE TABLE', 'VIEW', 'SYSTEM VIEW')
                     ORDER BY TABLE_NAME",
                ),
                'pgsql' => DB::connection($connectionName)->select(
                    "SELECT table_schema AS schema_name, table_name AS name, table_type AS type
                     FROM information_schema.tables
                     WHERE table_catalog = current_database()
                       AND table_schema NOT IN ('information_schema', 'pg_catalog')
                       AND table_type IN ('BASE TABLE', 'VIEW', 'FOREIGN')
                     ORDER BY table_schema, table_name",
                ),
                'sqlsrv' => DB::connection($connectionName)->select(
                    "SELECT schemas.name AS schema_name, objects.name AS name,
                            CASE WHEN objects.type = 'V' THEN 'VIEW' ELSE 'BASE TABLE' END AS type
                     FROM sys.objects AS objects
                     INNER JOIN sys.schemas AS schemas ON schemas.schema_id = objects.schema_id
                     WHERE objects.type IN ('U', 'V')
                     ORDER BY schemas.name, objects.name",
                ),
                'sqlite' => DB::connection($connectionName)->select(
                    "SELECT name, upper(type) AS type FROM sqlite_master
                     WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%'
                     ORDER BY name",
                ),
                default => [],
            };

            return self::$relationCache[$cacheKey] = collect($rows)
                ->mapWithKeys(function (object|array $row) use ($connection): array {
                    $name = trim((string) self::value($row, 'name'));
                    $schema = trim((string) self::value($row, 'schema_name'));
                    $type = strtoupper(trim((string) self::value($row, 'type')));
                    $qualifiedName = filled($schema) && $connection->database_driver !== 'mysql'
                        ? "{$schema}.{$name}"
                        : $name;
                    $typeLabel = str_contains($type, 'VIEW')
                        ? __('aho.data_integration.relation_types.view')
                        : __('aho.data_integration.relation_types.table');

                    return filled($name) ? [$qualifiedName => "{$qualifiedName} · {$typeLabel}"] : [];
                })
                ->all();
        } catch (Throwable $e) {
            throw ExternalDatabaseConnection::friendlyFailure($e);
        } finally {
            ExternalDatabaseConnection::disconnect($connectionName);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function columns(DataIntegrationConnection $connection, string $relation): array
    {
        if (blank($connection->database_name) || blank($relation)) {
            return [];
        }

        $connectionName = ExternalDatabaseConnection::configure($connection);

        try {
            [$schema, $name] = self::splitRelationName($relation, $connection);

            $rows = match ($connection->database_driver) {
                'mysql' => DB::connection($connectionName)->select(
                    'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                    [$name],
                ),
                'pgsql' => DB::connection($connectionName)->select(
                    'SELECT column_name AS name FROM information_schema.columns WHERE table_catalog = current_database() AND table_schema = ? AND table_name = ? ORDER BY ordinal_position',
                    [$schema, $name],
                ),
                'sqlsrv' => DB::connection($connectionName)->select(
                    'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_CATALOG = DB_NAME() AND TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                    [$schema, $name],
                ),
                default => collect(Schema::connection($connectionName)->getColumnListing($relation))
                    ->map(fn (string $column): object => (object) ['name' => $column])
                    ->all(),
            };

            return collect($rows)
                ->map(fn (object|array $row): string => trim((string) self::value($row, 'name')))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $e) {
            throw ExternalDatabaseConnection::friendlyFailure($e);
        } finally {
            ExternalDatabaseConnection::disconnect($connectionName);
        }
    }

    public static function clearCache(): void
    {
        self::$databaseCache = [];
        self::$relationCache = [];
    }

    private static function cacheKey(DataIntegrationConnection $connection, string $scope): string
    {
        return hash('sha256', json_encode([
            $scope,
            $connection->database_driver,
            $connection->server_name,
            $connection->port,
            $connection->database_name,
            $connection->username,
            hash('sha256', (string) $connection->password),
            $connection->ssl_mode,
            $connection->ssl_ca_path,
            $connection->ssl_certificate_path,
            $connection->ssl_key_path,
        ]));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitRelationName(string $relation, DataIntegrationConnection $connection): array
    {
        $parts = collect(explode('.', $relation))
            ->map(fn (string $part): string => trim($part, " \t\n\r\0\x0B`\"[]"))
            ->filter()
            ->values();

        if ($parts->count() > 1) {
            return [(string) $parts->first(), (string) $parts->last()];
        }

        return [match ($connection->database_driver) {
            'pgsql', 'sqlsrv' => 'public',
            default => (string) $connection->database_name,
        }, (string) $parts->first()];
    }

    private static function value(object|array $row, string $key): mixed
    {
        $values = array_change_key_case((array) $row, CASE_LOWER);

        return $values[Str::lower($key)] ?? null;
    }
}
