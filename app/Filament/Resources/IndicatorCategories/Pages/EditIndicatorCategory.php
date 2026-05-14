<?php

namespace App\Filament\Resources\IndicatorCategories\Pages;

use App\Filament\Resources\IndicatorCategories\IndicatorCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndicatorCategory extends EditRecord
{
    protected static string $resource = IndicatorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
