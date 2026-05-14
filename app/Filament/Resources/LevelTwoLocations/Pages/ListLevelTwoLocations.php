<?php

namespace App\Filament\Resources\LevelTwoLocations\Pages;

use App\Filament\Resources\LevelTwoLocations\LevelTwoLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLevelTwoLocations extends ListRecords
{
    protected static string $resource = LevelTwoLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
