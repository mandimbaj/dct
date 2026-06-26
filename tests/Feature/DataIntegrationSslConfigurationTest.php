<?php

namespace Tests\Feature;

use App\Models\DataIntegrationConnection;
use App\Support\DataIntegration\ExternalDatabaseConnection;
use PDO;
use Pdo\Mysql;
use RuntimeException;
use Tests\TestCase;

class DataIntegrationSslConfigurationTest extends TestCase
{
    public function test_mysql_verify_identity_uses_the_bundled_ca_and_timeout(): void
    {
        $connection = new DataIntegrationConnection([
            'database_driver' => 'mysql',
            'server_name' => 'example.mysql.database.azure.com',
            'port' => 3306,
            'database_name' => 'warehouse',
            'username' => 'reader',
            'password' => 'secret',
            'ssl_mode' => DataIntegrationConnection::SSL_MODE_VERIFY_IDENTITY,
            'connection_timeout' => 27,
        ]);

        $config = ExternalDatabaseConnection::configuration($connection);
        $caAttribute = $this->mysqlAttribute('ATTR_SSL_CA');
        $verifyAttribute = $this->mysqlAttribute('ATTR_SSL_VERIFY_SERVER_CERT');

        $this->assertSame(27, $config['options'][PDO::ATTR_TIMEOUT]);
        $this->assertSame(
            realpath(resource_path('certificates/DigiCertGlobalRootG2.crt.pem')),
            $config['options'][$caAttribute],
        );
        $this->assertTrue($config['options'][$verifyAttribute]);
    }

    public function test_mysql_required_encrypts_without_identity_verification(): void
    {
        $connection = new DataIntegrationConnection([
            'database_driver' => 'mysql',
            'server_name' => 'example.mysql.database.azure.com',
            'database_name' => 'warehouse',
            'ssl_mode' => DataIntegrationConnection::SSL_MODE_REQUIRED,
        ]);

        $config = ExternalDatabaseConnection::configuration($connection);

        $this->assertFalse(
            $config['options'][$this->mysqlAttribute('ATTR_SSL_VERIFY_SERVER_CERT')],
        );
    }

    public function test_an_invalid_custom_ca_path_returns_an_actionable_error(): void
    {
        $connection = new DataIntegrationConnection([
            'database_driver' => 'mysql',
            'server_name' => 'example.mysql.database.azure.com',
            'database_name' => 'warehouse',
            'ssl_mode' => DataIntegrationConnection::SSL_MODE_VERIFY_IDENTITY,
            'ssl_ca_path' => 'C:\\missing\\azure-ca.pem',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('C:\\missing\\azure-ca.pem');

        ExternalDatabaseConnection::configuration($connection);
    }

    private function mysqlAttribute(string $name): int
    {
        if (PHP_VERSION_ID >= 80500 && class_exists(Mysql::class)) {
            return constant(Mysql::class.'::'.$name);
        }

        return constant(PDO::class.'::MYSQL_'.$name);
    }
}
