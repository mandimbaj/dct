<?php

namespace App\Filament\Resources\Indicators;

use App\Filament\Clusters\Indicators as IndicatorsCluster;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Indicators\Pages\CreateIndicator;
use App\Filament\Resources\Indicators\Pages\EditIndicator;
use App\Filament\Resources\Indicators\Pages\ListIndicators;
use App\Filament\Resources\Indicators\Schemas\IndicatorForm;
use App\Filament\Resources\Indicators\Tables\IndicatorsTable;
use App\Models\Indicator;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorResource extends Resource
{
    protected static ?string $model = Indicator::class;

    protected static ?string $cluster = IndicatorsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'definitions';

    protected static ?string $navigationLabel = 'Indicators';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'indicator';

    protected static ?string $pluralModelLabel = 'indicators';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicators.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicators.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicators.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'afrocode',
            'gen_code',
            'translations.name',
            'translations.shortname',
            'translations.definition',
            'reference.code',
            'reference.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return IndicatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndicatorsTable::configure($table);
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
            'index' => ListIndicators::route('/'),
            'create' => CreateIndicator::route('/create'),
            'edit' => EditIndicator::route('/{record}/edit'),
        ];
    }
}
