<?php

namespace App\Filament\Resources\UhcCountrySelections\Pages;

use App\Filament\Resources\UhcCountrySelections\UhcCountrySelectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUhcCountrySelection extends CreateRecord
{
    protected static string $resource = UhcCountrySelectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
