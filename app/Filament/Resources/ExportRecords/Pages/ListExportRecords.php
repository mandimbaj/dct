<?php

namespace App\Filament\Resources\ExportRecords\Pages;

use App\Filament\Resources\ExportRecords\ExportRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExportRecords extends ListRecords
{
    protected static string $resource = ExportRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
