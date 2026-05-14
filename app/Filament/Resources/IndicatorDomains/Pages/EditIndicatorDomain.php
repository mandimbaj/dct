<?php

namespace App\Filament\Resources\IndicatorDomains\Pages;

use App\Filament\Resources\IndicatorDomains\IndicatorDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndicatorDomain extends EditRecord
{
    protected static string $resource = IndicatorDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
