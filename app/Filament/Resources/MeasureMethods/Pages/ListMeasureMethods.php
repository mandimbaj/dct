<?php

namespace App\Filament\Resources\MeasureMethods\Pages;

use App\Filament\Resources\MeasureMethods\MeasureMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeasureMethods extends ListRecords
{
    protected static string $resource = MeasureMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
