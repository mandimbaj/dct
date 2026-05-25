<?php

namespace App\Filament\Resources\UhcPriorityIndicators\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\UhcPriorityIndicators\UhcPriorityIndicatorResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditUhcPriorityIndicator extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = UhcPriorityIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
