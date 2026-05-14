<?php

namespace App\Filament\Resources\ResourceCategories\Pages;

use App\Filament\Resources\ResourceCategories\ResourceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResourceCategory extends CreateRecord
{
    protected static string $resource = ResourceCategoryResource::class;
}
