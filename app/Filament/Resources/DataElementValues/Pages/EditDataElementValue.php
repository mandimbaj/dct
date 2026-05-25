<?php

namespace App\Filament\Resources\DataElementValues\Pages;

use App\Filament\Resources\DataElementValues\DataElementValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditDataElementValue extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = DataElementValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
