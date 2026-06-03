<?php

namespace App\Filament\Resources\InstitutionTypes\Pages;

use App\Filament\Resources\InstitutionTypes\InstitutionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionTypes extends ListRecords
{
    protected static string $resource = InstitutionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
