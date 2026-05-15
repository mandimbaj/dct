<?php

namespace App\Filament\Resources\HealthCadres\Pages;

use App\Filament\Resources\HealthCadres\HealthCadreResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditHealthCadre extends EditRecord
{
    protected static string $resource = HealthCadreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
