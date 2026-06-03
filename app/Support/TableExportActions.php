<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Component;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TableExportActions
{
    public static function appendTo(Table $table): void
    {
        $table->pushToolbarActions([
            ActionGroup::make([
                Action::make('ahoExportCsv')
                    ->label(__('aho.table_exports.csv'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->action(fn (Component $livewire): StreamedResponse => self::downloadCsv($livewire)),
                Action::make('ahoExportXlsx')
                    ->label(__('aho.table_exports.excel'))
                    ->icon(Heroicon::OutlinedTableCells)
                    ->action(fn (Component $livewire): BinaryFileResponse => self::downloadXlsx($livewire)),
            ])
                ->label(__('aho.table_exports.label'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->button()
                ->color('gray')
                ->visible(fn (Component $livewire): bool => self::hasExportableRecords($livewire)),
        ]);
    }

    private static function hasExportableRecords(Component $livewire): bool
    {
        if (! $livewire instanceof HasTable) {
            return false;
        }

        try {
            return (clone $livewire->getTableQueryForExport())
                ->limit(1)
                ->exists();
        } catch (Throwable) {
            return true;
        }
    }

    private static function downloadCsv(Component $livewire): StreamedResponse
    {
        [$columns, $query, $fileName] = self::exportContext($livewire, 'csv');

        return response()->streamDownload(function () use ($columns, $query): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, self::headers($columns));

            foreach ($query->lazy(500) as $record) {
                fputcsv($handle, self::row($columns, $record));
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function downloadXlsx(Component $livewire): BinaryFileResponse
    {
        [$columns, $query, $fileName] = self::exportContext($livewire, 'xlsx');

        $directory = storage_path('app/exports');
        File::ensureDirectoryExists($directory);

        $path = tempnam($directory, 'table-export-');

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::headers($columns)));

        foreach ($query->lazy(500) as $record) {
            $writer->addRow(Row::fromValues(self::row($columns, $record)));
        }

        $writer->close();

        return response()->download($path, $fileName)->deleteFileAfterSend();
    }

    /**
     * @return array{0: array<int, Column>, 1: Builder, 2: string}
     */
    private static function exportContext(Component $livewire, string $extension): array
    {
        abort_unless($livewire instanceof HasTable, 404);

        $table = $livewire->getTable();
        $columns = array_values(array_filter(
            $table->getVisibleColumns(),
            fn ($column): bool => $column instanceof Column && method_exists($column, 'getState'),
        ));

        abort_if($columns === [], 422, __('aho.table_exports.no_columns'));

        return [
            $columns,
            clone $livewire->getTableQueryForExport(),
            self::fileName($table, $extension),
        ];
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<int, string>
     */
    private static function headers(array $columns): array
    {
        return array_map(fn (Column $column): string => self::plainText($column->getLabel()), $columns);
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<int, mixed>
     */
    private static function row(array $columns, Model $record): array
    {
        return array_map(function (Column $column) use ($record): mixed {
            $column = clone $column;
            $column->record($record);
            $column->clearCachedState();

            $state = $column->getState();

            if (method_exists($column, 'formatState')) {
                $state = $column->formatState($state);
            }

            return self::sanitize(self::plainText($state));
        }, $columns);
    }

    private static function fileName(Table $table, string $extension): string
    {
        $label = Str::of($table->getPluralModelLabel())
            ->ascii()
            ->slug('-')
            ->value() ?: 'export';

        return $label.'-'.now()->format('Ymd-His').'.'.$extension;
    }

    private static function plainText(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        if (is_array($value)) {
            $value = implode(', ', array_map(self::plainText(...), $value));
        }

        return trim(strip_tags((string) $value));
    }

    private static function sanitize(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
