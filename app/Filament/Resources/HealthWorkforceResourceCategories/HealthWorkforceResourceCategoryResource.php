<?php

namespace App\Filament\Resources\HealthWorkforceResourceCategories;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthWorkforceResourceCategories\Pages\CreateHealthWorkforceResourceCategory;
use App\Filament\Resources\HealthWorkforceResourceCategories\Pages\EditHealthWorkforceResourceCategory;
use App\Filament\Resources\HealthWorkforceResourceCategories\Pages\ListHealthWorkforceResourceCategories;
use App\Filament\Resources\ResourceCategories\ResourceCategoryResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Health workforce view of resource categories.
 *
 * The resource-category table is shared by multiple publication areas, so this resource filters
 * to the Django workforce category marker.
 */
class HealthWorkforceResourceCategoryResource extends ResourceCategoryResource
{
    use UsesFallbackResourcePermission;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'resource-categories';

    protected static ?int $navigationSort = 7;

    protected static function fallbackPermissionResources(): array
    {
        return [];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_workforce_resource_categories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_workforce_resource_categories.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_workforce_resource_categories.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        // category = 2 identifies Health workforce categories in the legacy warehouse.
        return parent::getEloquentQuery()
            ->where('category', 2);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthWorkforceResourceCategories::route('/'),
            'create' => CreateHealthWorkforceResourceCategory::route('/create'),
            'edit' => EditHealthWorkforceResourceCategory::route('/{record}/edit'),
        ];
    }
}
