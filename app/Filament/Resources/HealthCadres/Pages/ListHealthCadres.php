<?php

namespace App\Filament\Resources\HealthCadres\Pages;

use App\Filament\Resources\HealthCadres\HealthCadreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthCadres extends ListRecords
{
    protected static string $resource = HealthCadreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
