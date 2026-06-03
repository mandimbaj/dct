<?php

namespace App\Filament\Resources\EventAnnouncements\Pages;

use App\Filament\Resources\EventAnnouncements\EventAnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventAnnouncement extends CreateRecord
{
    protected static string $resource = EventAnnouncementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
