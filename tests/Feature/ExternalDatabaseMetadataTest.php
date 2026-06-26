<?php

namespace Tests\Feature;

use App\Models\DataIntegrationConnection;
use App\Support\DataIntegration\ExternalDatabaseMetadata;
use PDO;
use Tests\TestCase;

class ExternalDatabaseMetadataTest extends TestCase
{
    public function test_it_lists_sqlite_tables_views_and_view_columns(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'aho-integration-');
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec('CREATE TABLE indicators (id INTEGER PRIMARY KEY, indicator_name TEXT, value NUMERIC)');
        $pdo->exec('CREATE VIEW indicator_values AS SELECT indicator_name, value FROM indicators');

        $connection = new DataIntegrationConnection([
            'integration_method' => DataIntegrationConnection::METHOD_DIRECT,
            'database_driver' => 'sqlite',
            'database_name' => $path,
        ]);

        try {
            $relations = ExternalDatabaseMetadata::relations($connection);

            $this->assertArrayHasKey('indicators', $relations);
            $this->assertArrayHasKey('indicator_values', $relations);
            $this->assertSame(
                ['indicator_name', 'value'],
                ExternalDatabaseMetadata::columns($connection, 'indicator_values'),
            );
        } finally {
            @unlink($path);
        }
    }
}
