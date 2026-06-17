<?php

namespace App\Filament\Resources\DataElements\Pages;

use App\Filament\Resources\DataElements\DataElementResource;
use App\Filament\Resources\Pages\EditTranslatedRecord as EditRecord;

class EditDataElement extends EditRecord
{
    protected static string $resource = DataElementResource::class;
}
