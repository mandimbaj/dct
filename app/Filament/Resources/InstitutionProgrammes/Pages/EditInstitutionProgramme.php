<?php

namespace App\Filament\Resources\InstitutionProgrammes\Pages;

use App\Filament\Resources\InstitutionProgrammes\InstitutionProgrammeResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditInstitutionProgramme extends EditRecord
{
    protected static string $resource = InstitutionProgrammeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
