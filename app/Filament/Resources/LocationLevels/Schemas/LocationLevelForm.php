<?php

namespace App\Filament\Resources\LocationLevels\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class LocationLevelForm
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
