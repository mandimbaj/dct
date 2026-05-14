<?php

namespace App\Filament\Resources\IndicatorReferences\Pages;

use App\Filament\Resources\IndicatorReferences\IndicatorReferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicatorReferences extends ListRecords
{
    protected static string $resource = IndicatorReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
