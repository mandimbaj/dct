<?php

namespace App\Filament\Resources\HealthServiceIndicators\Pages;

use App\Filament\Resources\HealthServiceIndicators\HealthServiceIndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthServiceIndicators extends ListRecords
{
    protected static string $resource = HealthServiceIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
