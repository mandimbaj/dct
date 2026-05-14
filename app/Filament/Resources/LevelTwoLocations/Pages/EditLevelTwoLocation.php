<?php

namespace App\Filament\Resources\LevelTwoLocations\Pages;

use App\Filament\Resources\LevelTwoLocations\LevelTwoLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLevelTwoLocation extends EditRecord
{
    protected static string $resource = LevelTwoLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
