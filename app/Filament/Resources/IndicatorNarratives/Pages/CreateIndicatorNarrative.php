<?php

namespace App\Filament\Resources\IndicatorNarratives\Pages;

use App\Filament\Resources\IndicatorNarratives\IndicatorNarrativeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIndicatorNarrative extends CreateRecord
{
    protected static string $resource = IndicatorNarrativeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
