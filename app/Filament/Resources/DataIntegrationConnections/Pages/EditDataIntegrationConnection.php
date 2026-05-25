<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditDataIntegrationConnection extends EditRecord
{
    protected static string $resource = DataIntegrationConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configure_mapping')
                ->label(__('aho.data_integration.actions.configure_mapping'))
                ->icon('heroicon-o-arrows-right-left')
                ->url(fn (): string => $this->getResource()::getUrl('mapping', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }
}
