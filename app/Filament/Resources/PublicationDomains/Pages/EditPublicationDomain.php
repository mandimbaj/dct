<?php

namespace App\Filament\Resources\PublicationDomains\Pages;

use App\Filament\Resources\PublicationDomains\PublicationDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicationDomain extends EditRecord
{
    protected static string $resource = PublicationDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
