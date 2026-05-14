<?php

namespace App\Filament\Resources\IndicatorReferences\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorReferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->required()
                    ->maxLength(50),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
