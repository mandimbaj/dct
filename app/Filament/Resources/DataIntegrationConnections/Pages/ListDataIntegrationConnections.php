<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataIntegrationConnections extends ListRecords
{
    protected static string $resource = DataIntegrationConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
