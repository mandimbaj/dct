<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->required()
                    ->maxLength(15),
                TextInput::make('iso_alpha')
                    ->label(__('aho.fields.iso_alpha'))
                    ->required()
                    ->maxLength(15),
                TextInput::make('iso_number')
                    ->label(__('aho.fields.iso_numeric'))
                    ->required()
                    ->maxLength(15),
                Select::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload(),
                Select::make('locationlevel_id')
                    ->label(__('aho.fields.level'))
                    ->relationship('locationLevel', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('special_id')
                    ->label(__('aho.fields.special_status'))
                    ->required()
                    ->numeric(),
                TextInput::make('wb_income_id')
                    ->label(__('aho.fields.income_group'))
                    ->required()
                    ->numeric(),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->required()
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}
