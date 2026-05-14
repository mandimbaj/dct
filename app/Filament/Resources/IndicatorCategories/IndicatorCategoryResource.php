<?php

namespace App\Filament\Resources\IndicatorCategories;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\IndicatorCategories\Pages\CreateIndicatorCategory;
use App\Filament\Resources\IndicatorCategories\Pages\EditIndicatorCategory;
use App\Filament\Resources\IndicatorCategories\Pages\ListIndicatorCategories;
use App\Filament\Resources\IndicatorCategories\Schemas\IndicatorCategoryForm;
use App\Filament\Resources\IndicatorCategories\Tables\IndicatorCategoriesTable;
use App\Models\IndicatorCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorCategoryResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = IndicatorCategory::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'disaggregations';

    protected static ?string $navigationLabel = 'Disaggregations';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'disaggregation';

    protected static ?string $pluralModelLabel = 'disaggregations';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.disaggregations.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.disaggregations.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.disaggregations.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return IndicatorCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndicatorCategoriesTable::configure($table);
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
            'index' => ListIndicatorCategories::route('/'),
            'create' => CreateIndicatorCategory::route('/create'),
            'edit' => EditIndicatorCategory::route('/{record}/edit'),
        ];
    }
}
