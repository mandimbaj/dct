<?php

namespace App\Filament\Resources\RecurringEvents\Pages;

use App\Filament\Resources\RecurringEvents\RecurringEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecurringEvent extends CreateRecord
{
    protected static string $resource = RecurringEventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
