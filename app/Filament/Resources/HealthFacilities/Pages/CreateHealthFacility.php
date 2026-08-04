<?php

namespace App\Filament\Resources\HealthFacilities\Pages;

use App\Filament\Resources\HealthFacilities\HealthFacilityResource;
use App\Support\UserCountryAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthFacility extends CreateRecord
{
    protected static string $resource = HealthFacilityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->enforceCountryData($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enforceCountryData(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data);
    }
}
