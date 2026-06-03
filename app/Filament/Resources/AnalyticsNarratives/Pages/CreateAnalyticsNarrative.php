<?php

namespace App\Filament\Resources\AnalyticsNarratives\Pages;

use App\Filament\Resources\AnalyticsNarratives\AnalyticsNarrativeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticsNarrative extends CreateRecord
{
    protected static string $resource = AnalyticsNarrativeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
