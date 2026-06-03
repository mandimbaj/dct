<?php

namespace App\Filament\Resources\RecurringEvents\Pages;

use App\Filament\Resources\RecurringEvents\RecurringEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecurringEvents extends ListRecords
{
    protected static string $resource = RecurringEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
