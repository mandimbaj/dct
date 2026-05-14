<?php

namespace App\Filament\Resources\FacilityTypes\Pages;

use App\Filament\Resources\FacilityTypes\FacilityTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFacilityTypes extends ListRecords
{
    protected static string $resource = FacilityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
