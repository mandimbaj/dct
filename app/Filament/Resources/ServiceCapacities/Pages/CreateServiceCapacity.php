<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\Pages\Concerns\PrefillsFacilityFromRequest;
use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCapacity extends CreateRecord
{
    use EnforcesCountryFacilityData;
    use PrefillsFacilityFromRequest;

    protected static string $resource = ServiceCapacityResource::class;
}
