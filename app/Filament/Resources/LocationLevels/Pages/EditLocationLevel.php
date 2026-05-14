<?php

namespace App\Filament\Resources\LocationLevels\Pages;

use App\Filament\Resources\LocationLevels\LocationLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocationLevel extends EditRecord
{
    protected static string $resource = LocationLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
