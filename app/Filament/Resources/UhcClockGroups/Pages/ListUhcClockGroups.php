<?php

namespace App\Filament\Resources\UhcClockGroups\Pages;

use App\Filament\Resources\UhcClockGroups\UhcClockGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUhcClockGroups extends ListRecords
{
    protected static string $resource = UhcClockGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
