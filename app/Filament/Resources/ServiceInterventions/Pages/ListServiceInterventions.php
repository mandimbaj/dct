<?php

namespace App\Filament\Resources\ServiceInterventions\Pages;

use App\Filament\Resources\ServiceInterventions\ServiceInterventionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceInterventions extends ListRecords
{
    protected static string $resource = ServiceInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
