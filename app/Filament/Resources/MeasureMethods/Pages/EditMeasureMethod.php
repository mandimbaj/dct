<?php

namespace App\Filament\Resources\MeasureMethods\Pages;

use App\Filament\Resources\MeasureMethods\MeasureMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeasureMethod extends EditRecord
{
    protected static string $resource = MeasureMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
