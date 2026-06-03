<?php

namespace App\Filament\Resources\IndicatorNarratives\Pages;

use App\Filament\Resources\IndicatorNarratives\IndicatorNarrativeResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditIndicatorNarrative extends EditRecord
{
    protected static string $resource = IndicatorNarrativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
