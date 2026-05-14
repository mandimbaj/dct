<?php

namespace App\Filament\Resources\IndicatorReferences\Pages;

use App\Filament\Resources\IndicatorReferences\IndicatorReferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndicatorReference extends EditRecord
{
    protected static string $resource = IndicatorReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
