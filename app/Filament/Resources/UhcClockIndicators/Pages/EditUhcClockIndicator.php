<?php

namespace App\Filament\Resources\UhcClockIndicators\Pages;

use App\Filament\Resources\UhcClockIndicators\UhcClockIndicatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUhcClockIndicator extends EditRecord
{
    protected static string $resource = UhcClockIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
