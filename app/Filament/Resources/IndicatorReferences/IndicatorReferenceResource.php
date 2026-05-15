<?php

namespace App\Filament\Resources\IndicatorReferences;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\IndicatorReferences\Pages\CreateIndicatorReference;
use App\Filament\Resources\IndicatorReferences\Pages\EditIndicatorReference;
use App\Filament\Resources\IndicatorReferences\Pages\ListIndicatorReferences;
use App\Filament\Resources\IndicatorReferences\Schemas\IndicatorReferenceForm;
use App\Filament\Resources\IndicatorReferences\Tables\IndicatorReferencesTable;
use App\Models\IndicatorReference;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorReferenceResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = IndicatorReference::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'references';

    protected static ?string $navigationLabel = 'Indicator references';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'indicator reference';

    protected static ?string $pluralModelLabel = 'indicator references';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_references.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_references.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_references.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return IndicatorReferenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndicatorReferencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicatorReferences::route('/'),
            'create' => CreateIndicatorReference::route('/create'),
            'edit' => EditIndicatorReference::route('/{record}/edit'),
        ];
    }
}
