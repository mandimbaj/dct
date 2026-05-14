<?php

namespace App\Filament\Resources\FailedImportRows\Pages;

use App\Filament\Resources\FailedImportRows\FailedImportRowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFailedImportRows extends ListRecords
{
    protected static string $resource = FailedImportRowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
