<?php

namespace App\Filament\Resources\ValueDataTypes\Pages;

use App\Filament\Resources\ValueDataTypes\ValueDataTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListValueDataTypes extends ListRecords
{
    protected static string $resource = ValueDataTypeResource::class;
}
