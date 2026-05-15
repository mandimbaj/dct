<?php

namespace App\Filament\Resources\TrainingInstitutions\Pages;

use App\Filament\Resources\TrainingInstitutions\TrainingInstitutionResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditTrainingInstitution extends EditRecord
{
    protected static string $resource = TrainingInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
