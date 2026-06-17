<?php

namespace App\Filament\Resources\UhcClockFacts;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\UhcClockFacts\Pages\ListUhcClockFacts;
use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use App\Models\UhcClockFact;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UhcClockFactResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = UhcClockFact::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'facts';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_facts.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_clock_facts.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_clock_facts.plural');
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::simple(
            table: $table,
            columns: [
                'fact_id' => 'id',
                'afrocode' => 'code',
                'indicator' => 'indicator',
                'location' => 'location',
                'categoryoption' => 'category_option',
                'datasource' => 'source',
                'measure_type' => 'measure_type',
                'value_received' => 'value_received',
                'period' => 'period',
                'uhclock_theme' => 'theme',
                'comment' => 'status',
            ],
            defaultSort: 'fact_id',
            numericColumns: ['fact_id', 'indicator_id', 'datasource_id', 'value_received', 'start_period', 'end_period'],
            direction: 'desc',
        );
    }

    protected static function fallbackPermissionResources(): array
    {
        return [UhcPriorityIndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUhcClockFacts::route('/'),
        ];
    }
}
