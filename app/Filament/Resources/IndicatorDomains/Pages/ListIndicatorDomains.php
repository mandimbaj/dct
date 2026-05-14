<?php

namespace App\Filament\Resources\IndicatorDomains\Pages;

use App\Filament\Resources\IndicatorDomains\IndicatorDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicatorDomains extends ListRecords
{
    protected static string $resource = IndicatorDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
