<?php

namespace App\Filament\Resources\UhcClockThemes\Pages;

use App\Filament\Resources\UhcClockThemes\UhcClockThemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUhcClockThemes extends ListRecords
{
    protected static string $resource = UhcClockThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
