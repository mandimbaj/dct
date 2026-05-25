<?php

namespace App\Filament\Resources\HealthFacilities\Pages;

use App\Filament\Resources\HealthFacilities\HealthFacilityResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Models\FacilityOwner;
use App\Support\UserCountryAccess;
use Filament\Actions\DeleteAction;
use Illuminate\Validation\ValidationException;

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
        $data = UserCountryAccess::enforceLocationData($data);

        if (
            ! UserCountryAccess::canViewAllCountries()
            && filled($data['owner_id'] ?? null)
            && ! UserCountryAccess::scopedRecordExists(FacilityOwner::class, $data['owner_id'])
        ) {
            throw ValidationException::withMessages([
                'data.owner_id' => __('validation.exists', ['attribute' => __('aho.fields.owner')]),
            ]);
        }

        return $data;
    }
}
