<?php

namespace App\Filament\Resources\ServiceCapacities\Pages;

use App\Filament\Resources\ServiceCapacities\ServiceCapacityResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditServiceCapacity extends EditRecord
{
    protected static string $resource = ServiceCapacityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
