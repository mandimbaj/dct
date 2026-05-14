<?php

namespace App\Filament\Resources\ImportRecords\Pages;

use App\Filament\Resources\ImportRecords\ImportRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportRecords extends ListRecords
{
    protected static string $resource = ImportRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
