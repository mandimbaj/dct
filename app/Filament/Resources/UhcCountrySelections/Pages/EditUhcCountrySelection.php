<?php

namespace App\Filament\Resources\UhcCountrySelections\Pages;

use App\Filament\Resources\UhcCountrySelections\UhcCountrySelectionResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditUhcCountrySelection extends EditRecord
{
    protected static string $resource = UhcCountrySelectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
