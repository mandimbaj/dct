<?php

namespace App\Filament\Resources\DisaggregationCategories;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\DisaggregationCategories\Pages\EditDisaggregationCategory;
use App\Filament\Resources\DisaggregationCategories\Pages\CreateDisaggregationCategory;
use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\DisaggregationCategories\Pages\ListDisaggregationCategories;
use App\Filament\Resources\IndicatorCategories\IndicatorCategoryResource;
use App\Models\CategoryParent;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DisaggregationCategoryResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = CategoryParent::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'disaggregation-categories';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.disaggregation_categories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.disaggregation_categories.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.disaggregation_categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'category_id', 'parent_category')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function fallbackPermissionResources(): array
    {
        return [IndicatorCategoryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisaggregationCategories::route('/'),
            'create' => CreateDisaggregationCategory::route('/create'),
            'edit' => EditDisaggregationCategory::route('/{record}/edit'),
        ];
    }
}
