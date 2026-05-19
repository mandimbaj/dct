<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Support\WarehouseLocale;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uuid'),

                Section::make(__('aho.form_sections.location_details'))
                    ->schema([
                        Select::make('translation_language_code')
                            ->label(__('aho.fields.language'))
                            ->options(fn (): array => WarehouseLocale::supported())
                            ->default(fn (): string => WarehouseLocale::current())
                            ->required(),
                        TextInput::make('translation_name')
                            ->label(__('aho.fields.name'))
                            ->required()
                            ->maxLength(230),
                        Select::make('locationlevel_id')
                            ->label(__('aho.fields.level'))
                            ->relationship('locationLevel', 'code')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->searchable()
                            ->required(),
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
                        Textarea::make('translation_description')
                            ->label(__('aho.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('aho.form_sections.geo_map'))
                    ->schema([
                        Select::make('parent_id')
                            ->label(__('aho.fields.parent'))
                            ->relationship('parent', 'code')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->searchable(),
                        TextInput::make('translation_longitude')
                            ->label(__('aho.fields.longitude'))
                            ->numeric(),
                        TextInput::make('translation_latitude')
                            ->label(__('aho.fields.latitude'))
                            ->numeric(),
                        Textarea::make('translation_cordinate')
                            ->label(__('aho.fields.coordinate'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('aho.form_sections.socioeconomic_status'))
                    ->schema([
                        TextInput::make('special_id')
                            ->label(__('aho.fields.special_status'))
                            ->required()
                            ->numeric(),
                        TextInput::make('wb_income_id')
                            ->label(__('aho.fields.income_group'))
                            ->required()
                            ->numeric(),
                    ])
                    ->columns(2),
            ]);
    }
}
