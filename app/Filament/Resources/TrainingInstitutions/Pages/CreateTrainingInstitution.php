<?php

namespace App\Filament\Resources\TrainingInstitutions\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\TrainingInstitutions\TrainingInstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingInstitution extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = TrainingInstitutionResource::class;
}
