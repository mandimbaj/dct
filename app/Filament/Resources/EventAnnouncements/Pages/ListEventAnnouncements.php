<?php

namespace App\Filament\Resources\EventAnnouncements\Pages;

use App\Filament\Resources\EventAnnouncements\EventAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventAnnouncements extends ListRecords
{
    protected static string $resource = EventAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
