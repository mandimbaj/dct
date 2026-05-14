<?php

namespace App\Filament\Resources\HealthIndicatorValues;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\HealthIndicatorValues\Pages\CreateHealthIndicatorValue;
use App\Filament\Resources\HealthIndicatorValues\Pages\EditHealthIndicatorValue;
use App\Filament\Resources\HealthIndicatorValues\Pages\ListHealthIndicatorValues;
use App\Filament\Resources\HealthIndicatorValues\Schemas\HealthIndicatorValueForm;
use App\Filament\Resources\HealthIndicatorValues\Tables\HealthIndicatorValuesTable;
use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HealthIndicatorValueResource extends Resource
{
    protected static ?string $model = HealthIndicatorValue::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'values';

    protected static ?string $navigationLabel = 'Indicator values';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'indicator value';

    protected static ?string $pluralModelLabel = 'indicator values';

    protected static ?string $recordTitleAttribute = 'fact_id';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_values.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'period',
            'comment',
            'indicator.afrocode',
            'indicator.gen_code',
            'indicator.translations.name',
            'indicator.translations.shortname',
            'location.code',
            'location.iso_alpha',
            'location.translations.name',
            'categoryOption.code',
            'categoryOption.translations.name',
            'dataSource.code',
            'dataSource.translations.name',
            'measureMethod.code',
            'measureMethod.translations.name',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return trim('#'.$record->fact_id.' '.($record->indicator?->afrocode ?? ''));
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('aho.fields.indicator') => $record->indicator?->display_name,
            __('aho.fields.location') => $record->location?->display_name,
            __('aho.fields.period') => $record->period,
            __('aho.fields.value_received') => (string) $record->value_received,
            __('aho.fields.approval_status') => ApprovalWorkflow::label($record->comment),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return HealthIndicatorValueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HealthIndicatorValuesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(parent::getEloquentQuery());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthIndicatorValues::route('/'),
            'create' => CreateHealthIndicatorValue::route('/create'),
            'edit' => EditHealthIndicatorValue::route('/{record}/edit'),
        ];
    }
}
