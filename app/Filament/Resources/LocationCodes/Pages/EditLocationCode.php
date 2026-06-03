<?php

namespace App\Filament\Resources\LocationCodes\Pages;

use App\Filament\Resources\LocationCodes\LocationCodeResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditLocationCode extends EditRecord
{
    protected static string $resource = LocationCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
