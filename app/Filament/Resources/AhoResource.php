<?php

namespace App\Filament\Resources;

use App\Support\TableExportActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Table;

abstract class AhoResource extends Resource
{
    public static function configureTable(Table $table): void
    {
        parent::configureTable($table);

        if (static::getGloballySearchableAttributes() !== []) {
            $table->pushRecordActions([
                ViewAction::make(),
            ]);
        }

        TableExportActions::appendTo($table);
    }
}
