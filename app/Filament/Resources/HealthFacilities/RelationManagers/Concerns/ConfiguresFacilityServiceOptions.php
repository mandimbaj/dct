<?php

namespace App\Filament\Resources\HealthFacilities\RelationManagers\Concerns;

use App\Models\FacilityProvisionUnit;
use App\Models\FacilityServiceArea;
use App\Models\FacilityServiceDomain;
use App\Models\FacilityServiceIntervention;
use App\Support\SelectOptions;
use Illuminate\Database\Eloquent\Builder;

trait ConfiguresFacilityServiceOptions
{
    /**
     * @return array<int, string>
     */
    protected static function domainOptions(int $category, ?string $level = null, ?string $search = null): array
    {
        $query = static::domainQuery($category, $level);

        if (! $query->exists() && filled($level)) {
            $query = static::domainQuery($category);
        }

        if (! $query->exists()) {
            $query = FacilityServiceDomain::query()->with('translations');
        }

        return SelectOptions::fromDisplayNameQuery(
            $query,
            $search,
            'domain_id',
        );
    }

    protected static function domainQuery(int $category, ?string $level = null): Builder
    {
        return FacilityServiceDomain::query()
            ->with('translations')
            ->where('category', $category)
            ->when(
                filled($level),
                fn (Builder $query): Builder => $query->whereRaw('LOWER(level) = ?', [strtolower((string) $level)]),
            );
    }

    /**
     * @return array<int, string>
     */
    protected static function unitOptions(mixed $domainId, ?string $search = null): array
    {
        if (blank($domainId)) {
            return [];
        }

        return SelectOptions::fromDisplayNameQuery(
            FacilityProvisionUnit::query()
                ->with('translations')
                ->where('domain_id', (int) $domainId),
            $search,
            'infra_id',
        );
    }

    /**
     * @return array<int, string>
     */
    protected static function interventionOptions(mixed $domainId, ?string $search = null): array
    {
        if (blank($domainId)) {
            return [];
        }

        return SelectOptions::fromDisplayNameQuery(
            FacilityServiceIntervention::query()
                ->with('translations')
                ->where('domain_id', (int) $domainId),
            $search,
            'intervention_id',
        );
    }

    /**
     * @return array<int, string>
     */
    protected static function serviceAreaOptions(mixed $interventionId, ?string $search = null): array
    {
        if (blank($interventionId)) {
            return [];
        }

        return SelectOptions::fromDisplayNameQuery(
            FacilityServiceArea::query()
                ->with('translations')
                ->where('intervention_id', (int) $interventionId),
            $search,
            'area_id',
        );
    }
}
