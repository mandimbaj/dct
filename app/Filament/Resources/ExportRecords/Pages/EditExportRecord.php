<?php

namespace App\Filament\Resources\ExportRecords\Pages;

use App\Filament\Resources\ExportRecords\ExportRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExportRecord extends EditRecord
{
    protected static string $resource = ExportRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
