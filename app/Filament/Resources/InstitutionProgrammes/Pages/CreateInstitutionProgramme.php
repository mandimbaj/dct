<?php

namespace App\Filament\Resources\InstitutionProgrammes\Pages;

use App\Filament\Resources\InstitutionProgrammes\InstitutionProgrammeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitutionProgramme extends CreateRecord
{
    protected static string $resource = InstitutionProgrammeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
