<?php

namespace App\Filament\Resources\TrainingInstitutions\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\TrainingInstitutions\TrainingInstitutionResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditTrainingInstitution extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = TrainingInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
