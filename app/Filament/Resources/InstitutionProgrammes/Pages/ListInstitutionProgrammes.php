<?php

namespace App\Filament\Resources\InstitutionProgrammes\Pages;

use App\Filament\Resources\InstitutionProgrammes\InstitutionProgrammeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionProgrammes extends ListRecords
{
    protected static string $resource = InstitutionProgrammeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
