<?php

namespace App\Filament\Resources\LevelTwoLocations\Pages;

use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\LevelTwoLocations\LevelTwoLocationResource;
use App\Support\UserCountryAccess;
use Filament\Actions\DeleteAction;

class EditLevelTwoLocation extends EditRecord
{
    protected static string $resource = LevelTwoLocationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data, 'parent_id');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
