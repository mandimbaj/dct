<?php

namespace App\Filament\Resources\ServiceDomains\Pages;

use App\Filament\Resources\ServiceDomains\ServiceDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceDomains extends ListRecords
{
    protected static string $resource = ServiceDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
