<?php

namespace App\Filament\Resources\HealthWorkforceValues\Pages;

use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthWorkforceValue extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = HealthWorkforceValueResource::class;
}
