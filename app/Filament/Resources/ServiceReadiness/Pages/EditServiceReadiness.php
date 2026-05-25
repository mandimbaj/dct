<?php

namespace App\Filament\Resources\ServiceReadiness\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\ServiceReadiness\ServiceReadinessResource;
use Filament\Actions\DeleteAction;

class EditServiceReadiness extends EditRecord
{
    use EnforcesCountryFacilityData;

    protected static string $resource = ServiceReadinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
