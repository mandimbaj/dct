<?php

namespace App\Filament\Resources\TimePeriods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TimePeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->maxLength(50)
                    ->required(),
                TextInput::make('name')
                    ->label(__('aho.fields.name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('shortname')
                    ->label(__('aho.fields.short_name'))
                    ->maxLength(50),
                TextInput::make('description')
                    ->label(__('aho.fields.description'))
                    ->maxLength(500),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
