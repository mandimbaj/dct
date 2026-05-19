<?php

namespace App\Filament\Resources\Indicators\Schemas;

use App\Support\WarehouseLocale;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IndicatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uuid'),

                Section::make(__('aho.form_sections.primary_attributes'))
                    ->schema([
                        Select::make('translation_language_code')
                            ->label(__('aho.fields.language'))
                            ->options(fn (): array => WarehouseLocale::supported())
                            ->default(fn (): string => WarehouseLocale::current())
                            ->required(),
                        TextInput::make('translation_name')
                            ->label(__('aho.fields.name'))
                            ->required()
                            ->maxLength(500),
                        TextInput::make('translation_shortname')
                            ->label(__('aho.fields.short_name'))
                            ->maxLength(120),
                        Select::make('reference_id')
                            ->label(__('aho.fields.reference'))
                            ->relationship('reference', 'code')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->searchable()
                            ->required(),
                        TextInput::make('afrocode')
                            ->label(__('aho.fields.afro_code'))
                            ->required()
                            ->maxLength(10),
                        TextInput::make('gen_code')
                            ->label(__('aho.fields.general_code'))
                            ->maxLength(10),
                        Textarea::make('translation_definition')
                            ->label(__('aho.fields.definition'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('aho.form_sections.secondary_attributes'))
                    ->schema([
                        Textarea::make('translation_numerator_description')
                            ->label(__('aho.fields.numerator_description'))
                            ->rows(3),
                        Textarea::make('translation_denominator_description')
                            ->label(__('aho.fields.denominator_description'))
                            ->rows(3),
                        Textarea::make('translation_preferred_datasources')
                            ->label(__('aho.fields.preferred_datasources'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
