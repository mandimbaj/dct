<?php

namespace App\Filament\Resources\Pages\Concerns;

use App\Support\UserCountryAccess;

trait EnforcesCountryLocationData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data);
    }
}
