<?php

namespace App\Filament\Resources\ServiceReadiness\Pages;

use App\Filament\Resources\ServiceReadiness\ServiceReadinessResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceReadiness extends EditRecord
{
    protected static string $resource = ServiceReadinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
