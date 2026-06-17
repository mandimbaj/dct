<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\Facilities\Pages\Concerns\ListsFacilityServiceProxyRecords;
use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use App\Models\HealthFacility;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceCapacities extends ListRecords
{
    use ListsFacilityServiceProxyRecords;

    protected static string $resource = ServiceCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

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

    protected function serviceCreateLabel(): string
    {
        return __('aho.actions.add_service_capacity');
    }

    protected function serviceCreateUrl(HealthFacility $record): string
    {
        return ServiceCapacityResource::getUrl('create', [
            'country' => $this->countryRouteParameter(),
            'facility_id' => $record->getKey(),
        ]);
    }
}
