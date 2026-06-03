<?php

namespace App\Filament\Resources\InstitutionTypes\Pages;

use App\Filament\Resources\InstitutionTypes\InstitutionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitutionType extends CreateRecord
{
    protected static string $resource = InstitutionTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
