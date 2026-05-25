<?php

namespace App\Filament\Resources\HealthServiceValues\Pages;

use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditHealthServiceValue extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = HealthServiceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
