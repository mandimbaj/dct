<?php

namespace App\Filament\Resources\IndicatorDomains\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorDomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->required()
                    ->maxLength(45),
                TextInput::make('level')
                    ->label(__('aho.fields.level'))
                    ->required()
                    ->maxLength(50),
                Select::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload(),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
