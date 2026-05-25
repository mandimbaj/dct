<?php

namespace App\Filament\Resources\UhcPriorityIndicators\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUhcPriorityIndicator extends CreateRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = UhcPriorityIndicatorResource::class;
}
