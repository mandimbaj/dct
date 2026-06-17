<?php

namespace App\Filament\Resources\HealthServiceProgrammes\Pages;

use App\Filament\Resources\HealthServiceProgrammes\HealthServiceProgrammeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthServiceProgrammes extends ListRecords
{
    protected static string $resource = HealthServiceProgrammeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
