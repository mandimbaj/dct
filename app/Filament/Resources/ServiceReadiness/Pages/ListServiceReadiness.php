<?php

namespace App\Filament\Resources\ServiceReadiness\Pages;

use App\Filament\Resources\Facilities\Pages\Concerns\ListsFacilityServiceProxyRecords;
use App\Filament\Resources\ServiceReadiness\ServiceReadinessResource;
use App\Models\HealthFacility;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceReadiness extends ListRecords
{
    use ListsFacilityServiceProxyRecords;

    protected static string $resource = ServiceReadinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function serviceRelationship(): string
    {
        return 'serviceReadiness';
    }

    protected function serviceCountColumn(): string
    {
        return 'service_readiness_count';
    }

    protected function serviceLatestAssessmentColumn(): string
    {
        return 'service_readiness_max_date_assessed';
    }

    protected function serviceCreateLabel(): string
    {
        return __('aho.actions.add_service_readiness');
    }

    protected function serviceCreateUrl(HealthFacility $record): string
    {
        return ServiceReadinessResource::getUrl('create', [
            'country' => $this->countryRouteParameter(),
            'facility_id' => $record->getKey(),
        ]);
    }

    protected function serviceRelationIndex(): int
    {
        return 2;
    }
}
