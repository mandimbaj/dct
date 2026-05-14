<?php

namespace App\Filament\Resources\ServiceInterventions\Pages;

use App\Filament\Resources\ServiceInterventions\ServiceInterventionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceIntervention extends EditRecord
{
    protected static string $resource = ServiceInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
