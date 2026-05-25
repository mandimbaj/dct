<?php

namespace App\Filament\Resources\MeasureMethods\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class MeasureMethodForm
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
