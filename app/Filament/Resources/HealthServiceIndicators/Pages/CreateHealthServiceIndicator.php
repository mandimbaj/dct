<?php

namespace App\Filament\Resources\HealthServiceIndicators\Pages;

use App\Filament\Resources\HealthServiceIndicators\HealthServiceIndicatorResource;
use App\Filament\Resources\Indicators\Pages\CreateIndicator;

class CreateHealthServiceIndicator extends CreateIndicator
{
    protected static string $resource = HealthServiceIndicatorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference_id'] = $data['reference_id'] ?? HealthServiceIndicatorResource::hscReferenceId();

        return parent::mutateFormDataBeforeCreate($data);
    }
}
