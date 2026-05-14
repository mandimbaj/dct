<?php

namespace App\Filament\Resources\FacilityOwners\Pages;

use App\Filament\Resources\FacilityOwners\FacilityOwnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFacilityOwner extends EditRecord
{
    protected static string $resource = FacilityOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
