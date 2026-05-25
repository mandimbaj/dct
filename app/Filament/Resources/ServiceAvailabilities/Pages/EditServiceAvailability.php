<?php

namespace App\Filament\Resources\ServiceAvailabilities\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\ServiceAvailabilities\ServiceAvailabilityResource;
use Filament\Actions\DeleteAction;

class EditServiceAvailability extends EditRecord
{
    use EnforcesCountryFacilityData;

    protected static string $resource = ServiceAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
