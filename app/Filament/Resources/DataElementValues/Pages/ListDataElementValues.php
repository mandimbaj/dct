<?php

namespace App\Filament\Resources\DataElementValues\Pages;

use App\Filament\Resources\DataElementValues\DataElementValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataElementValues extends ListRecords
{
    protected static string $resource = DataElementValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
