<?php

namespace App\Filament\Resources\DataElementGroups\Pages;

use App\Filament\Resources\DataElementGroups\DataElementGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataElementGroup extends EditRecord
{
    protected static string $resource = DataElementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
