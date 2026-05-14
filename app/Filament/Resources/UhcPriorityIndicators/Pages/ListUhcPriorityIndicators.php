<?php

namespace App\Filament\Resources\UhcPriorityIndicators\Pages;

use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUhcPriorityIndicators extends ListRecords
{
    protected static string $resource = UhcPriorityIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
