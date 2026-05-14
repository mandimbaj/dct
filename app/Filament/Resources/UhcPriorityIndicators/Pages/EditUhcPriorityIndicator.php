<?php

namespace App\Filament\Resources\UhcPriorityIndicators\Pages;

use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUhcPriorityIndicator extends EditRecord
{
    protected static string $resource = UhcPriorityIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
