<?php

namespace App\Filament\Resources\TrainingInstitutions\Pages;

use App\Filament\Resources\TrainingInstitutions\TrainingInstitutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingInstitutions extends ListRecords
{
    protected static string $resource = TrainingInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
