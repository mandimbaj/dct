<?php

namespace App\Filament\Resources\FailedImportRows\Pages;

use App\Filament\Resources\FailedImportRows\FailedImportRowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFailedImportRow extends CreateRecord
{
    protected static string $resource = FailedImportRowResource::class;
}
