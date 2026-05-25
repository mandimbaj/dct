<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDataIntegrationConnection extends CreateRecord
{
    protected static string $resource = DataIntegrationConnectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('mapping', ['record' => $this->getRecord()]);
    }
}
