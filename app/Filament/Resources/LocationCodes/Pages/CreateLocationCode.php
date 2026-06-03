<?php

namespace App\Filament\Resources\LocationCodes\Pages;

use App\Filament\Resources\LocationCodes\LocationCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocationCode extends CreateRecord
{
    protected static string $resource = LocationCodeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
