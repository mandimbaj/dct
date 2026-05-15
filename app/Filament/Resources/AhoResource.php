<?php

namespace App\Filament\Resources;

use App\Support\TableExportActions;
use Filament\Resources\Resource;
use Filament\Tables\Table;

abstract class AhoResource extends Resource
{
    public static function configureTable(Table $table): void
    {
        parent::configureTable($table);

        TableExportActions::appendTo($table);
    }
}
