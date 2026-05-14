<?php

namespace App\Filament\Resources\UhcClockIndicators\Pages;

use App\Filament\Resources\UhcClockIndicators\UhcClockIndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUhcClockIndicators extends ListRecords
{
    protected static string $resource = UhcClockIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
