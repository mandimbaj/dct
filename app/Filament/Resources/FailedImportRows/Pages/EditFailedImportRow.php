<?php

namespace App\Filament\Resources\FailedImportRows\Pages;

use App\Filament\Resources\FailedImportRows\FailedImportRowResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditFailedImportRow extends EditRecord
{
    protected static string $resource = FailedImportRowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
