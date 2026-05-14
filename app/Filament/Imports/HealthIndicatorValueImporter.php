<?php

namespace App\Filament\Imports;

use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class HealthIndicatorValueImporter extends Importer
{
    protected static ?string $model = HealthIndicatorValue::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('indicator')
                ->label(__('aho.fields.indicator'))
                ->requiredMapping()
                ->relationship(resolveUsing: ['indicator_id', 'afrocode'])
                ->rules(['required']),
            ImportColumn::make('location')
                ->label(__('aho.fields.location'))
                ->requiredMapping()
                ->relationship(resolveUsing: ['location_id', 'code', 'iso_alpha'])
                ->rules(['required']),
            ImportColumn::make('period')
                ->label(__('aho.fields.period'))
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('start_period')
                ->label(__('aho.fields.start'))
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('end_period')
                ->label(__('aho.fields.end'))
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('categoryOption')
                ->label(__('aho.fields.disaggregation'))
                ->requiredMapping()
                ->relationship(resolveUsing: ['categoryoption_id', 'code'])
                ->rules(['required']),
            ImportColumn::make('dataSource')
                ->label(__('aho.fields.source'))
                ->requiredMapping()
                ->relationship(resolveUsing: ['datasource_id', 'code'])
                ->rules(['required']),
            ImportColumn::make('measureMethod')
                ->label(__('aho.fields.method'))
                ->requiredMapping()
                ->relationship(resolveUsing: ['measuremethod_id', 'code'])
                ->rules(['required']),
            ImportColumn::make('value_received')
                ->label(__('aho.fields.value_received'))
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('numerator_value')
                ->label(__('aho.fields.numerator'))
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('denominator_value')
                ->label(__('aho.fields.denominator'))
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('min_value')
                ->label(__('aho.fields.min'))
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('max_value')
                ->label(__('aho.fields.max'))
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('target_value')
                ->label(__('aho.fields.target'))
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('string_value')
                ->label(__('aho.fields.text_value'))
                ->rules(['nullable', 'max:500']),
            ImportColumn::make('comment')
                ->label(__('aho.fields.approval_status'))
                ->rules(['nullable', Rule::in([
                    ApprovalWorkflow::STATUS_PENDING,
                    ApprovalWorkflow::STATUS_APPROVED,
                    ApprovalWorkflow::STATUS_REJECTED,
                ])]),
            ImportColumn::make('uuid')
                ->label(__('aho.fields.uuid'))
                ->rules(['nullable', 'max:36']),
        ];
    }

    public function resolveRecord(): HealthIndicatorValue
    {
        return new HealthIndicatorValue;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('aho.import.completed', [
            'rows' => Number::format($import->successful_rows),
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__('aho.import.failed', [
                'rows' => Number::format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
