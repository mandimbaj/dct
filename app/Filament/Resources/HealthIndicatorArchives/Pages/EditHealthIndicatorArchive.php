<?php

namespace App\Filament\Resources\HealthIndicatorArchives\Pages;

use App\Filament\Resources\HealthIndicatorArchives\HealthIndicatorArchiveResource;
use App\Support\UserCountryAccess;
use Filament\Resources\Pages\EditRecord;

class EditHealthIndicatorArchive extends EditRecord
{
    protected static string $resource = HealthIndicatorArchiveResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserCountryAccess::enforceLocationData($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
