<?php

namespace App\Filament\Resources\IndicatorCategories\Pages;

use App\Filament\Resources\IndicatorCategories\IndicatorCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicatorCategories extends ListRecords
{
    protected static string $resource = IndicatorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
