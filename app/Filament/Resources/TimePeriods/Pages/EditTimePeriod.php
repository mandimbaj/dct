<?php

namespace App\Filament\Resources\TimePeriods\Pages;

use App\Filament\Resources\TimePeriods\TimePeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimePeriod extends EditRecord
{
    protected static string $resource = TimePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
