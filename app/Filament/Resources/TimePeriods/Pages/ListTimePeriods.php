<?php

namespace App\Filament\Resources\TimePeriods\Pages;

use App\Filament\Resources\TimePeriods\TimePeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimePeriods extends ListRecords
{
    protected static string $resource = TimePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
