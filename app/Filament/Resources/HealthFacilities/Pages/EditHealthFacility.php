<?php

namespace App\Filament\Resources\HealthFacilities\Pages;

use App\Filament\Resources\HealthFacilities\HealthFacilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHealthFacility extends EditRecord
{
    protected static string $resource = HealthFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
