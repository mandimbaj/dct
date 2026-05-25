<?php

namespace App\Filament\Resources\DataSources\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class DataSourceForm
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
