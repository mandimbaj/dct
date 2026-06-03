<?php

namespace App\Filament\Resources\IncomeGroups\Pages;

use App\Filament\Resources\IncomeGroups\IncomeGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomeGroups extends ListRecords
{
    protected static string $resource = IncomeGroupResource::class;
}
