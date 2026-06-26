<?php

namespace App\Filament\Resources\NationalObservatories\Pages;

use App\Filament\Resources\NationalObservatories\NationalObservatoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNationalObservatories extends ListRecords
{
    protected static string $resource = NationalObservatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => NationalObservatoryResource::canCreateForAvailableCountry()),
        ];
    }
}
