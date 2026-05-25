<?php

namespace App\Filament\Resources\DataElementValues\Pages;

use App\Filament\Resources\DataElementValues\DataElementValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use Filament\Resources\Pages\CreateRecord;

class CreateDataElementValue extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = DataElementValueResource::class;
}
