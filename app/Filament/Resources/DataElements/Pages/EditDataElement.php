<?php

namespace App\Filament\Resources\DataElements\Pages;

use App\Filament\Resources\DataElements\DataElementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataElement extends EditRecord
{
    protected static string $resource = DataElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
