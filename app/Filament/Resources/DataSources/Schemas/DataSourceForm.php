<?php

namespace App\Filament\Resources\DataSources\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DataSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->maxLength(50),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->required()
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
