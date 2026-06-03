<?php

namespace App\Filament\Resources\IndicatorDomainLookups;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\IndicatorDomainLookups\Pages\ListIndicatorDomainLookups;
use App\Filament\Resources\IndicatorDomains\IndicatorDomainResource;
use App\Models\IndicatorDomainLookup;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorDomainLookupResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = IndicatorDomainLookup::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'themes-lookup';

    protected static ?int $navigationSort = 14;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_domain_lookup.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_domain_lookup.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_domain_lookup.plural');
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::simple(
            table: $table,
            columns: [
                'indicator_id' => 'indicator_id',
                'indicator_name' => 'indicator',
                'code' => 'code',
                'domain_name' => 'theme',
                'domain_level' => 'level',
            ],
            defaultSort: 'indicator_name',
            numericColumns: ['indicator_id', 'domain_level'],
        );
    }

    protected static function fallbackPermissionResources(): array
    {
        return [IndicatorDomainResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicatorDomainLookups::route('/'),
        ];
    }
}
