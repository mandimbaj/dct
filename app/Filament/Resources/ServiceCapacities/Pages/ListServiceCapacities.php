<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\Facilities\Pages\Concerns\ListsFacilityServiceProxyRecords;
use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceCapacities extends ListRecords
{
    use ListsFacilityServiceProxyRecords;

    protected static string $resource = ServiceCapacityResource::class;

    protected function serviceRelationship(): string
    {
        return 'serviceCapacities';
    }

    protected function serviceCountColumn(): string
    {
        return 'service_capacities_count';
    }

    protected function serviceLatestAssessmentColumn(): string
    {
        return 'service_capacities_max_date_assessed';
    }

    protected function serviceRelationIndex(): int
    {
        return 1;
    }
}
