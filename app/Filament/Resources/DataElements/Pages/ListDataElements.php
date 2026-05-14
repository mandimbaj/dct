<?php

namespace App\Filament\Resources\DataElements\Pages;

use App\Filament\Resources\DataElements\DataElementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataElements extends ListRecords
{
    protected static string $resource = DataElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
