<?php

namespace App\Filament\Resources\FacilityTypes\Pages;

use App\Filament\Resources\FacilityTypes\FacilityTypeResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditFacilityType extends EditRecord
{
    protected static string $resource = FacilityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
