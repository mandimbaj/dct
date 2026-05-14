<?php

namespace App\Filament\Resources\ServiceDomains\Pages;

use App\Filament\Resources\ServiceDomains\ServiceDomainResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceDomain extends CreateRecord
{
    protected static string $resource = ServiceDomainResource::class;
}
