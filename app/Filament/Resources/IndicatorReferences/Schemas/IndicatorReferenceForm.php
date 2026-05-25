<?php

namespace App\Filament\Resources\IndicatorReferences\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class IndicatorReferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('code'),
                Hidden::make('uuid'),
            ]);
    }
}
