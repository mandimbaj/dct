<?php

namespace App\Filament\Resources\PublicationDomains\Pages;

use App\Filament\Resources\PublicationDomains\PublicationDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicationDomains extends ListRecords
{
    protected static string $resource = PublicationDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
