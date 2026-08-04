<?php

namespace App\Filament\Resources\UhcClockFacts;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\UhcClockFacts\Pages\ListUhcClockFacts;
use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use App\Models\Country;
use App\Models\UhcClockFact;
use App\Support\FilamentReadOnlyTables;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (UserCountryAccess::canViewAllCountries()) {
            return $query;
        }

        $locationNames = static::allowedLocationNamesForUhcFactView();

        return $locationNames === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn(DB::raw('upper(location)'), $locationNames);
    }

    /**
     * The UHC fact view exposes a text location column, not location_id.
     *
     * @return array<int, string>
     */
    private static function allowedLocationNamesForUhcFactView(): array
    {
        $locationIds = UserCountryAccess::allowedLocationIds();

        if ($locationIds === []) {
            return [];
        }

        return Country::query()
            ->with('translations')
            ->whereIn('location_id', $locationIds)
            ->get()
            ->flatMap(fn (Country $country): array => [
                $country->display_name,
                $country->code,
                $country->iso_alpha,
                ...$country->translations->pluck('name')->all(),
            ])
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => mb_strtoupper(trim((string) $name)))
            ->unique()
            ->values()
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUhcClockFacts::route('/'),
        ];
    }
}
