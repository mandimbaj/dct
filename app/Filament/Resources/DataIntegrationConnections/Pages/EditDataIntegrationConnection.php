<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditDataIntegrationConnection extends EditRecord
{
    protected static string $resource = DataIntegrationConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
