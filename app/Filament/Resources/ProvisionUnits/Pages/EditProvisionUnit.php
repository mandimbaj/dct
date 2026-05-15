<?php

namespace App\Filament\Resources\ProvisionUnits\Pages;

use App\Filament\Resources\ProvisionUnits\ProvisionUnitResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditProvisionUnit extends EditRecord
{
    protected static string $resource = ProvisionUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
