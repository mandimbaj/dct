<?php

namespace App\Console\Commands;

use App\Support\WarehouseUserSynchronizer;
use Illuminate\Console\Command;

class SyncWarehouseUsers extends Command
{
    protected $signature = 'users:sync-warehouse {--location= : Limit synchronization to one location ID}';

    protected $description = 'Synchronize legacy Django authentication users into the Laravel user directory.';

    public function handle(WarehouseUserSynchronizer $synchronizer): int
    {
        $locationId = filled($this->option('location')) ? (int) $this->option('location') : null;
        $summary = $synchronizer->sync($locationId);

        $this->components->info(
            "Synchronized {$summary['total']} Django user(s): {$summary['created']} created, {$summary['matched']} already present.",
        );

        return self::SUCCESS;
    }
}
