<?php

namespace App\Filament\Resources\DataElementValues\Pages;

use App\Filament\Resources\DataElementValues\DataElementValueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataElementValue extends EditRecord
{
    protected static string $resource = DataElementValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
