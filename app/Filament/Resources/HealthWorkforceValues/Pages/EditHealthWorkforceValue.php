<?php

namespace App\Filament\Resources\HealthWorkforceValues\Pages;

use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditHealthWorkforceValue extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = HealthWorkforceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
