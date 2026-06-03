<?php

namespace App\Filament\Resources\AnalyticsNarratives\Pages;

use App\Filament\Resources\AnalyticsNarratives\AnalyticsNarrativeResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditAnalyticsNarrative extends EditRecord
{
    protected static string $resource = AnalyticsNarrativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
