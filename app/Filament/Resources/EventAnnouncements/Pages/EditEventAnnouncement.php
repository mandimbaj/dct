<?php

namespace App\Filament\Resources\EventAnnouncements\Pages;

use App\Filament\Resources\EventAnnouncements\EventAnnouncementResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\Pages\Concerns\EnforcesCountryLocationData;
use Filament\Actions\DeleteAction;

class EditEventAnnouncement extends EditRecord
{
    use EnforcesCountryLocationData;

    protected static string $resource = EventAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
