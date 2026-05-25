<?php

namespace App\Filament\Resources\Pages\Concerns;

use App\Models\HealthFacility;
use App\Support\UserCountryAccess;
use Illuminate\Validation\ValidationException;

trait EnforcesCountryFacilityData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->enforceCountryFacilityData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->enforceCountryFacilityData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enforceCountryFacilityData(array $data): array
    {
        if (UserCountryAccess::canViewAllCountries() || blank($data['facility_id'] ?? null)) {
            return $data;
        }

        if (UserCountryAccess::scopedRecordExists(HealthFacility::class, $data['facility_id'])) {
            return $data;
        }

        throw ValidationException::withMessages([
            'data.facility_id' => __('validation.exists', ['attribute' => __('aho.fields.facility')]),
        ]);
    }
}
