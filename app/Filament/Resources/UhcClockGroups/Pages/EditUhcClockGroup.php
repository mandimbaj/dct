<?php

namespace App\Filament\Resources\UhcClockGroups\Pages;

use App\Filament\Resources\UhcClockGroups\UhcClockGroupResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditUhcClockGroup extends EditRecord
{
    protected static string $resource = UhcClockGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
