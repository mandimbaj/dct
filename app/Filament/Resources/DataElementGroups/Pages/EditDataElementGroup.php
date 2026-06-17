<?php

namespace App\Filament\Resources\DataElementGroups\Pages;

use App\Filament\Resources\DataElementGroups\DataElementGroupResource;
use App\Filament\Resources\Pages\EditTranslatedRecord as EditRecord;

class EditDataElementGroup extends EditRecord
{
    protected static string $resource = DataElementGroupResource::class;
}
