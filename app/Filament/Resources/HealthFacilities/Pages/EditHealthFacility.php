<?php

namespace App\Filament\Resources\HealthFacilities\Pages;

use App\Filament\Resources\HealthFacilities\HealthFacilityResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Support\UserCountryAccess;
use Filament\Actions\DeleteAction;

class EditHealthFacility extends EditRecord
{
    protected static string $resource = HealthFacilityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->enforceCountryData($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
