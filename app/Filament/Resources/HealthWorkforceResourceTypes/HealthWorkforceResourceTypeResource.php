<?php

namespace App\Filament\Resources\HealthWorkforceResourceTypes;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthWorkforceResourceTypes\Pages\CreateHealthWorkforceResourceType;
use App\Filament\Resources\HealthWorkforceResourceTypes\Pages\EditHealthWorkforceResourceType;
use App\Filament\Resources\HealthWorkforceResourceTypes\Pages\ListHealthWorkforceResourceTypes;
use App\Filament\Resources\ResourceTypes\ResourceTypeResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Health workforce view of resource types.
 *
 * Resource types are global lookup rows; this submenu only shows types connected to a
 * ResourceCategory row where category = 2.
 */
class HealthWorkforceResourceTypeResource extends ResourceTypeResource
{
    use UsesFallbackResourcePermission;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'resource-types';

    protected static ?int $navigationSort = 6;

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
        return __('aho.resources.health_workforce_resource_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_workforce_resource_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_workforce_resource_types.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        // Keep only resource types that have at least one Health workforce category.
        return parent::getEloquentQuery()
            ->whereHas('categories', fn (Builder $query): Builder => $query->where('category', 2));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthWorkforceResourceTypes::route('/'),
            'create' => CreateHealthWorkforceResourceType::route('/create'),
            'edit' => EditHealthWorkforceResourceType::route('/{record}/edit'),
        ];
    }
}
