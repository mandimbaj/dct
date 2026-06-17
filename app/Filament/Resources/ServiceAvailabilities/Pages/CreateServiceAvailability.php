<?php

namespace App\Filament\Resources\ServiceAvailabilities\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\Concerns\PrefillsFacilityFromRequest;
use App\Filament\Resources\ServiceAvailabilities\ServiceAvailabilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceAvailability extends CreateRecord
{
    use EnforcesCountryFacilityData;
    use PrefillsFacilityFromRequest;

    protected static string $resource = ServiceAvailabilityResource::class;
}
