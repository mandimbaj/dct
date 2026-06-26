<?php

namespace App\Support\DataIntegration;

use App\Models\DataIntegrationConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use Pdo\Mysql;
use RuntimeException;
use Throwable;

class ExternalDatabaseConnection
{
    /**
     * @return array<string, mixed>
     */
    public static function configuration(DataIntegrationConnection $connection): array
    {
        $driver = (string) $connection->database_driver;
        $timeout = min(120, max(1, (int) ($connection->connection_timeout ?: 15)));

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => (string) $connection->database_name,
                'prefix' => '',
            ];
        }

        $base = [
            'driver' => $driver,
            'host' => (string) $connection->server_name,
            'port' => $connection->port,
            'database' => (string) $connection->database_name,
            'username' => (string) $connection->username,
            'password' => (string) $connection->password,
        ];

        return match ($driver) {
            'mysql' => [
                ...$base,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'options' => self::mysqlOptions($connection, $timeout),
            ],
            'pgsql' => [
                ...$base,
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
                'sslmode' => self::postgresSslMode($connection),
                'sslrootcert' => self::optionalReadableFile(
                    $connection->ssl_ca_path,
                    __('aho.data_integration.fields.ssl_ca_path'),
                ),
                'sslcert' => self::optionalReadableFile(
                    $connection->ssl_certificate_path,
                    __('aho.data_integration.fields.ssl_certificate_path'),
                ),
                'sslkey' => self::optionalReadableFile(
                    $connection->ssl_key_path,
                    __('aho.data_integration.fields.ssl_key_path'),
                ),
                'connect_timeout' => $timeout,
            ],
            'sqlsrv' => [
                ...$base,
                'charset' => 'utf8',
                'prefix' => '',
                'encrypt' => $connection->ssl_mode !== DataIntegrationConnection::SSL_MODE_DISABLED,
                'trust_server_certificate' => $connection->ssl_mode === DataIntegrationConnection::SSL_MODE_REQUIRED,
                'login_timeout' => $timeout,
            ],
            default => [
                ...$base,
                'prefix' => '',
            ],
        };
    }

    public static function configure(DataIntegrationConnection $connection): string
    {
        $suffix = $connection->getKey() ?: spl_object_id($connection);
        $connectionName = 'external_data_integration_'.$suffix;

        Config::set("database.connections.{$connectionName}", self::configuration($connection));
        DB::purge($connectionName);

        return $connectionName;
    }

    public static function disconnect(string $connectionName): void
    {
        DB::disconnect($connectionName);
        DB::purge($connectionName);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function test(DataIntegrationConnection $connection): array
    {
        $connectionName = self::configure($connection);

        try {
            DB::connection($connectionName)->getPdo();
            $cipher = null;

            if ($connection->database_driver === 'mysql') {
                $row = DB::connection($connectionName)->selectOne("SHOW STATUS LIKE 'Ssl_cipher'");
                $cipher = $row?->Value ?? $row?->value ?? null;

                if (
                    $connection->ssl_mode !== DataIntegrationConnection::SSL_MODE_DISABLED
                    && blank($cipher)
                ) {
                    throw new RuntimeException(__('aho.data_integration.validation.ssl_not_negotiated'));
                }
            }

            return [
                'ok' => true,
                'message' => filled($cipher)
                    ? __('aho.data_integration.validation.direct_connection_success_tls', ['cipher' => $cipher])
                    : __('aho.data_integration.validation.direct_connection_success'),
            ];
        } catch (Throwable $e) {
            throw self::friendlyFailure($e);
        } finally {
            self::disconnect($connectionName);
        }
    }

    public static function friendlyFailure(Throwable $e): RuntimeException
    {
        $message = $e->getMessage();

        if (str_contains($message, '[3159]') || str_contains($message, 'require_secure_transport')) {
            return new RuntimeException(__('aho.data_integration.validation.tls_required'), previous: $e);
        }

        return $e instanceof RuntimeException && str_starts_with($message, (string) __('aho.data_integration.validation.error_prefix'))
            ? $e
            : new RuntimeException(
                __('aho.data_integration.validation.connection_failed', ['message' => $message]),
                previous: $e,
            );
    }

    /**
     * @return array<int, mixed>
     */
    private static function mysqlOptions(DataIntegrationConnection $connection, int $timeout): array
    {
        $options = [PDO::ATTR_TIMEOUT => $timeout];

        if ($connection->ssl_mode === DataIntegrationConnection::SSL_MODE_DISABLED) {
            return $options;
        }

        $caPath = filled($connection->ssl_ca_path)
            ? self::optionalReadableFile(
                $connection->ssl_ca_path,
                __('aho.data_integration.fields.ssl_ca_path'),
            )
            : resource_path('certificates/DigiCertGlobalRootG2.crt.pem');

        if (! is_file($caPath) || ! is_readable($caPath)) {
            throw new RuntimeException(__('aho.data_integration.validation.ssl_file_not_readable', [
                'field' => __('aho.data_integration.fields.ssl_ca_path'),
                'path' => $caPath,
            ]));
        }

        [$certificatePath, $keyPath] = self::clientCertificatePair($connection);

        $options[self::mysqlAttribute('ATTR_SSL_CA')] = realpath($caPath) ?: $caPath;
        $options[self::mysqlAttribute('ATTR_SSL_VERIFY_SERVER_CERT')] =
            $connection->ssl_mode === DataIntegrationConnection::SSL_MODE_VERIFY_IDENTITY;

        if ($certificatePath !== null) {
            $options[self::mysqlAttribute('ATTR_SSL_CERT')] = $certificatePath;
            $options[self::mysqlAttribute('ATTR_SSL_KEY')] = $keyPath;
        }

        if (filled($connection->ssl_cipher)) {
            $options[self::mysqlAttribute('ATTR_SSL_CIPHER')] = (string) $connection->ssl_cipher;
        }

        return $options;
    }

    private static function postgresSslMode(DataIntegrationConnection $connection): string
    {
        return match ($connection->ssl_mode) {
            DataIntegrationConnection::SSL_MODE_REQUIRED => 'require',
            DataIntegrationConnection::SSL_MODE_VERIFY_IDENTITY => 'verify-full',
            default => 'disable',
        };
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private static function clientCertificatePair(DataIntegrationConnection $connection): array
    {
        $certificate = self::optionalReadableFile(
            $connection->ssl_certificate_path,
            __('aho.data_integration.fields.ssl_certificate_path'),
        );
        $key = self::optionalReadableFile(
            $connection->ssl_key_path,
            __('aho.data_integration.fields.ssl_key_path'),
        );

        if (($certificate === null) !== ($key === null)) {
            throw new RuntimeException(__('aho.data_integration.validation.ssl_client_pair_required'));
        }

        return [$certificate, $key];
    }

    private static function optionalReadableFile(mixed $path, string $field): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(__('aho.data_integration.validation.ssl_file_not_readable', [
                'field' => $field,
                'path' => $path,
            ]));
        }

        return realpath($path) ?: $path;
    }

    private static function mysqlAttribute(string $name): int
    {
        if (PHP_VERSION_ID >= 80500 && class_exists(Mysql::class)) {
            return constant(Mysql::class.'::'.$name);
        }

        return constant(PDO::class.'::MYSQL_'.$name);
    }
}
