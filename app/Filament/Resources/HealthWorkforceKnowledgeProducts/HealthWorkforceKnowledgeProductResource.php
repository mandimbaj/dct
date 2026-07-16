<?php

namespace App\Filament\Resources\HealthWorkforceKnowledgeProducts;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthWorkforceKnowledgeProducts\Pages\CreateHealthWorkforceKnowledgeProduct;
use App\Filament\Resources\HealthWorkforceKnowledgeProducts\Pages\EditHealthWorkforceKnowledgeProduct;
use App\Filament\Resources\HealthWorkforceKnowledgeProducts\Pages\ListHealthWorkforceKnowledgeProducts;
use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Health workforce view of KnowledgeProduct records.
 *
 * This mirrors Django's HumanWorkforceResourceProxy by reusing the publication resource and
 * limiting it to categories marked with the historical workforce flag category = 2.
 */
class HealthWorkforceKnowledgeProductResource extends KnowledgeProductResource
{
    use UsesFallbackResourcePermission;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'resources-guides';

    protected static ?int $navigationSort = 2;

    protected static function fallbackPermissionResources(): array
    {
        return [];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_workforce_resources.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_workforce_resources.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_workforce_resources.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        // category = 2 is the legacy Django marker for Health workforce resources.
        return parent::getEloquentQuery()
            ->whereHas('category', fn (Builder $query): Builder => $query->where('category', 2));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthWorkforceKnowledgeProducts::route('/'),
            'create' => CreateHealthWorkforceKnowledgeProduct::route('/create'),
            'edit' => EditHealthWorkforceKnowledgeProduct::route('/{record}/edit'),
        ];
    }
}
