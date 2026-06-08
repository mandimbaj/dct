<?php

namespace App\Console\Commands;

use App\Support\ApprovalWorkflow;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ArchiveApprovedIndicatorValues extends Command
{
    protected $signature = 'indicators:archive-approved
        {--retention-days=90 : Number of days approved records remain in the active table}
        {--dry-run : Show what would be archived and deleted without changing data}';

    protected $description = 'Archive approved indicator values and remove active approved rows after the retention window.';

    /** @var list<string> */
    private const ARCHIVE_COLUMNS = [
        'uuid',
        'indicator_id',
        'location_id',
        'categoryoption_id',
        'datasource_id',
        'measuremethod_id',
        'numerator_value',
        'denominator_value',
        'value_received',
        'min_value',
        'max_value',
        'target_value',
        'string_value',
        'start_period',
        'end_period',
        'period',
        'comment',
        'user_id',
        'date_created',
        'date_lastupdated',
    ];

    public function handle(): int
    {
        $retentionDays = max(1, (int) $this->option('retention-days'));
        $cutoff = now()->subDays($retentionDays);
        $connection = DB::connection('warehouse');

        $approvedCount = $this->approvedValuesQuery($connection)->count();
        $olderApprovedCount = $this->approvedValuesQuery($connection)
            ->where('date_created', '<=', $cutoff)
            ->count();

        if ($this->option('dry-run')) {
            $this->components->info('Dry run only. No rows were changed.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Approved active rows', $approvedCount],
                    ["Approved active rows older than {$retentionDays} days", $olderApprovedCount],
                    ['Retention cutoff', $cutoff->toDateTimeString()],
                ],
            );

            return self::SUCCESS;
        }

        [$archived, $deleted] = $connection->transaction(function () use ($connection, $cutoff): array {
            $archived = $connection
                ->table('fact_data_archive')
                ->insertUsing(self::ARCHIVE_COLUMNS, $this->approvedValuesMissingFromArchiveQuery($connection));

            $deleted = $this->approvedValuesQuery($connection)
                ->where('date_created', '<=', $cutoff)
                ->whereExists(function (Builder $query): void {
                    $query
                        ->selectRaw('1')
                        ->from('fact_data_archive as archive')
                        ->whereColumn('archive.uuid', 'fact_data_indicators.uuid');
                })
                ->delete();

            return [(int) $archived, (int) $deleted];
        });

        $this->components->info("Archived {$archived} approved indicator value(s).");
        $this->components->info("Deleted {$deleted} active approved indicator value(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }

    private function approvedValuesQuery(ConnectionInterface $connection): Builder
    {
        return $connection
            ->table('fact_data_indicators')
            ->whereNotNull('uuid')
            ->whereRaw('lower(trim(comment)) = ?', [ApprovalWorkflow::STATUS_APPROVED]);
    }

    private function approvedValuesMissingFromArchiveQuery(ConnectionInterface $connection): Builder
    {
        return $connection
            ->table('fact_data_indicators as active')
            ->select(array_map(
                fn (string $column): string => "active.{$column}",
                self::ARCHIVE_COLUMNS,
            ))
            ->whereNotNull('active.uuid')
            ->whereRaw('lower(trim(active.comment)) = ?', [ApprovalWorkflow::STATUS_APPROVED])
            ->whereNotExists(function (Builder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('fact_data_archive as archive')
                    ->whereColumn('archive.uuid', 'active.uuid');
            });
    }
}
