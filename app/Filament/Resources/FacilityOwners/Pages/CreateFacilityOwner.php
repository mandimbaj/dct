<?php

namespace App\Filament\Resources\FacilityOwners\Pages;

use App\Filament\Resources\FacilityOwners\FacilityOwnerResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use Filament\Resources\Pages\CreateRecord;

class CreateFacilityOwner extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = FacilityOwnerResource::class;
}
