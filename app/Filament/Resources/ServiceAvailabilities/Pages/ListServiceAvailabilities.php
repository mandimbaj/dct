<?php

namespace App\Filament\Resources\ServiceAvailabilities\Pages;

use App\Filament\Resources\Facilities\Pages\Concerns\ListsFacilityServiceProxyRecords;
use App\Filament\Resources\ServiceAvailabilities\ServiceAvailabilityResource;
use App\Models\HealthFacility;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceAvailabilities extends ListRecords
{
    use ListsFacilityServiceProxyRecords;

    protected static string $resource = ServiceAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function serviceRelationship(): string
    {
        return 'serviceAvailabilities';
    }

    protected function serviceCountColumn(): string
    {
        return 'service_availabilities_count';
    }

    protected function serviceLatestAssessmentColumn(): string
    {
        return 'service_availabilities_max_date_assessed';
    }

    protected function serviceCreateLabel(): string
    {
        return __('aho.actions.add_service_availability');
    }

    protected function serviceCreateUrl(HealthFacility $record): string
    {
        return ServiceAvailabilityResource::getUrl('create', [
            'country' => $this->countryRouteParameter(),
            'facility_id' => $record->getKey(),
        ]);
    }

    protected function serviceRelationIndex(): int
    {
        return 0;
    }
}
