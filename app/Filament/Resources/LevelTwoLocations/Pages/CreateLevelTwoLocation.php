<?php

namespace App\Filament\Resources\LevelTwoLocations\Pages;

use App\Filament\Resources\LevelTwoLocations\LevelTwoLocationResource;
use App\Support\UserCountryAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateLevelTwoLocation extends CreateRecord
{
    protected static string $resource = LevelTwoLocationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data, 'parent_id');
    }
}
