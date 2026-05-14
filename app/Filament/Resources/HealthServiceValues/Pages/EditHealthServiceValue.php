<?php

namespace App\Filament\Resources\HealthServiceValues\Pages;

use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHealthServiceValue extends EditRecord
{
    protected static string $resource = HealthServiceValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
