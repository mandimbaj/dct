<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Actions\DeleteAction;

class EditServiceCapacity extends EditRecord
{
    use EnforcesCountryFacilityData;

    protected static string $resource = ServiceCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
