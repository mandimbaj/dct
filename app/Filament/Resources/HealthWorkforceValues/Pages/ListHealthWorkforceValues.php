<?php

namespace App\Filament\Resources\HealthWorkforceValues\Pages;

use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthWorkforceValues extends ListRecords
{
    protected static string $resource = HealthWorkforceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
