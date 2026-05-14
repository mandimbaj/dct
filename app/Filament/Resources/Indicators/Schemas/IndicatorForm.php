<?php

namespace App\Filament\Resources\Indicators\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reference_id')
                    ->label(__('aho.fields.reference'))
                    ->relationship('reference', 'code')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('afrocode')
                    ->label(__('aho.fields.afro_code'))
                    ->required()
                    ->maxLength(10),
                TextInput::make('gen_code')
                    ->label(__('aho.fields.general_code'))
                    ->maxLength(10),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
