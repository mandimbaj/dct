<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryFacilityData;
use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCapacity extends CreateRecord
{
    use EnforcesCountryFacilityData;

    protected static string $resource = ServiceCapacityResource::class;
}
