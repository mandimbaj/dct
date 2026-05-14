<?php

namespace App\Filament\Resources\ServiceReadiness\Pages;

use App\Filament\Resources\ServiceReadiness\ServiceReadinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceReadiness extends ListRecords
{
    protected static string $resource = ServiceReadinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
