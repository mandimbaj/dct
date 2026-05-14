<?php

namespace App\Filament\Resources\DataElementGroups\Pages;

use App\Filament\Resources\DataElementGroups\DataElementGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataElementGroups extends ListRecords
{
    protected static string $resource = DataElementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
