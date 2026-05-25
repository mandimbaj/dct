<?php

namespace App\Filament\Resources\FacilityOwners\Pages;

use App\Filament\Resources\FacilityOwners\FacilityOwnerResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditFacilityOwner extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = FacilityOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
