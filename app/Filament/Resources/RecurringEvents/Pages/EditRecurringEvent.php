<?php

namespace App\Filament\Resources\RecurringEvents\Pages;

use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\RecurringEvents\RecurringEventResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditRecurringEvent extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = RecurringEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
