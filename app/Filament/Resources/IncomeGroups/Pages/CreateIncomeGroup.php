<?php

namespace App\Filament\Resources\IncomeGroups\Pages;

use App\Filament\Resources\IncomeGroups\IncomeGroupResource;
use App\Filament\Resources\Pages\CreateTranslatedRecord;

class CreateIncomeGroup extends CreateTranslatedRecord
{
    protected static string $resource = IncomeGroupResource::class;
}
