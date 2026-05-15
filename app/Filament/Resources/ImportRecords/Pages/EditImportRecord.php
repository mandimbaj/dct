<?php

namespace App\Filament\Resources\ImportRecords\Pages;

use App\Filament\Resources\ImportRecords\ImportRecordResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditImportRecord extends EditRecord
{
    protected static string $resource = ImportRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
