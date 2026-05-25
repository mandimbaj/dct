<?php

namespace App\Filament\Resources\HealthServiceValues\Pages;

use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthServiceValue extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = HealthServiceValueResource::class;
}
