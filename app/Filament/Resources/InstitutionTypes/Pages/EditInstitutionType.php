<?php

namespace App\Filament\Resources\InstitutionTypes\Pages;

use App\Filament\Resources\InstitutionTypes\InstitutionTypeResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditInstitutionType extends EditRecord
{
    protected static string $resource = InstitutionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
