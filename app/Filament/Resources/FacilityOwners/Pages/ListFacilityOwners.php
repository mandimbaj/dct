<?php

namespace App\Filament\Resources\FacilityOwners\Pages;

use App\Filament\Resources\FacilityOwners\FacilityOwnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFacilityOwners extends ListRecords
{
    protected static string $resource = FacilityOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
