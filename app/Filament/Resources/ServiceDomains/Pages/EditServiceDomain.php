<?php

namespace App\Filament\Resources\ServiceDomains\Pages;

use App\Filament\Resources\ServiceDomains\ServiceDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceDomain extends EditRecord
{
    protected static string $resource = ServiceDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
