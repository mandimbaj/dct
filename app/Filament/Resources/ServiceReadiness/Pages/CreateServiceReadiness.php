<?php

namespace App\Filament\Resources\ServiceReadiness\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\Concerns\PrefillsFacilityFromRequest;
use App\Filament\Resources\ServiceReadiness\ServiceReadinessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceReadiness extends CreateRecord
{
    use EnforcesCountryFacilityData;
    use PrefillsFacilityFromRequest;

    protected static string $resource = ServiceReadinessResource::class;
}
