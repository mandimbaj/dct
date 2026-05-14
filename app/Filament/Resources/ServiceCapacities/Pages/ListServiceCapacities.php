<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceCapacities extends ListRecords
{
    protected static string $resource = ServiceCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
