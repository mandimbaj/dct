<?php

namespace App\Filament\Resources\HealthServiceValues\Pages;

use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthServiceValues extends ListRecords
{
    protected static string $resource = HealthServiceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
