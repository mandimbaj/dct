<?php

namespace App\Filament\Resources\UserPageVisits\Pages;

use App\Filament\Resources\UserPageVisits\UserPageVisitResource;
use Filament\Resources\Pages\ListRecords;

class ListUserPageVisits extends ListRecords
{
    protected static string $resource = UserPageVisitResource::class;
}
