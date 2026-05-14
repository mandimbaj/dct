<?php

namespace App\Filament\Resources\ProvisionUnits\Pages;

use App\Filament\Resources\ProvisionUnits\ProvisionUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProvisionUnits extends ListRecords
{
    protected static string $resource = ProvisionUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
