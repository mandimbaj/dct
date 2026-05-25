<?php

namespace App\Support;

use App\Models\Country;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CountryTableFilter
{
    public static function make(string $column = 'location_id', string $name = 'country_id'): SelectFilter
    {
        return SelectFilter::make($name)
            ->label(__('aho.fields.country'))
            ->options(fn (): array => self::countryOptions())
            ->searchable()
            ->preload()
            ->native(false)
            ->optionsLimit(SelectOptions::LIMIT)
            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(self::countryOptions(), $search))
            ->query(function (Builder $query, array $data) use ($column): Builder {
                $value = $data['value'] ?? null;

                if (blank($value)) {
                    return $query;
                }

                $locationIds = self::locationAndDescendantIds((int) $value);

                if ($locationIds === []) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereIn($query->getModel()->qualifyColumn($column), $locationIds);
            });
    }

    /**
     * @return array<int|string, string>
     */
    public static function countryOptions(): array
    {
        $scopeKey = UserCountryAccess::canViewAllCountries()
            ? 'all'
            : 'user-'.(UserCountryAccess::locationId() ?? 'none');

        return Cache::remember(
            'country-table-filter.options.'.WarehouseLocale::current().'.'.$scopeKey,
            now()->addMinutes(10),
            function (): array {
                $query = Country::query()
                    ->where('locationlevel_id', 2)
                    ->with('translations')
                    ->orderBy('code');

                if (! UserCountryAccess::canViewAllCountries()) {
                    $locationId = UserCountryAccess::locationId();

                    if (blank($locationId)) {
                        return [];
                    }

                    $query->where('location_id', (int) $locationId);
                }

                return $query
                    ->get()
                    ->mapWithKeys(fn (Country $country): array => [
                        $country->location_id => $country->display_name,
                    ])
                    ->sortBy(fn (string $label): string => mb_strtolower($label))
                    ->all();
            },
        );
    }

    /**
     * @return array<int, int>
     */
    public static function locationAndDescendantIds(int $locationId): array
    {
        if ($locationId <= 0) {
            return [];
        }

        $locations = self::locationTree();
        $childrenByParent = $locations->groupBy('parent_id');
        $ids = [];
        $stack = [$locationId];

        while ($stack !== []) {
            $current = array_pop($stack);

            if (in_array($current, $ids, true)) {
                continue;
            }

            $ids[] = $current;

            foreach ($childrenByParent->get($current, collect()) as $child) {
                $stack[] = (int) $child['location_id'];
            }
        }

        return $ids;
    }

    /**
     * @return Collection<int, array{location_id: int, parent_id: int|null}>
     */
    private static function locationTree(): Collection
    {
        return Cache::remember(
            'country-table-filter.location-tree',
            now()->addMinutes(30),
            fn (): Collection => Country::query()
                ->get(['location_id', 'parent_id'])
                ->map(fn (Country $location): array => [
                    'location_id' => (int) $location->location_id,
                    'parent_id' => filled($location->parent_id) ? (int) $location->parent_id : null,
                ]),
        );
    }
}
