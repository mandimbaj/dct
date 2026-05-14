<?php

namespace App\Support;

use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class MortalityIndicators
{
    /**
     * @param  Builder<HealthIndicatorValue>  $query
     * @return Builder<HealthIndicatorValue>
     */
    public static function scopeValues(Builder $query): Builder
    {
        $ids = static::ids();

        return $ids === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn($query->getModel()->qualifyColumn('indicator_id'), $ids);
    }

    /**
     * @return array<int, int>
     */
    public static function ids(): array
    {
        return Cache::remember('mortality-indicator-ids:v3', now()->addHour(), function (): array {
            return Indicator::query()
                ->whereHas('translations', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        foreach (static::terms() as $term) {
                            $query
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('shortname', 'like', "%{$term}%")
                                ->orWhere('definition', 'like', "%{$term}%");
                        }
                    });
                })
                ->pluck('indicator_id')
                ->all();
        });
    }

    /**
     * @return array<int, string>
     */
    private static function terms(): array
    {
        return [
            'mortality',
            'mortalit',
            'mortalidade',
            'death',
            'deces',
            'décès',
        ];
    }
}
