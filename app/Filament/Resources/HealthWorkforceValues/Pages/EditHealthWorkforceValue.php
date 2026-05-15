<?php

namespace App\Filament\Resources\HealthWorkforceValues\Pages;

use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditHealthWorkforceValue extends EditRecord
{
    protected static string $resource = HealthWorkforceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
