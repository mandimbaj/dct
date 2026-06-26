<?php

namespace App\Filament\Resources\DataIntegrationConnections;

use App\Filament\Resources\AhoResource as Resource;
use App\Models\DataIntegrationFieldMapping;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DataIntegrationFieldMappingResource extends Resource
{
    protected static ?string $model = DataIntegrationFieldMapping::class;

    protected static string|UnitEnum|null $navigationGroup = 'Data Integration';

    protected static ?string $slug = 'field-mappings';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('aho.data_integration.sections.field_mapping'))
                ->description(__('aho.data_integration.help.field_mapping_description'))
                ->schema([
                    Select::make('local_field')
                        ->label(__('aho.data_integration.fields.local_field'))
                        ->options(DataIntegrationFieldMapping::localFieldOptions())
                        ->searchable()
                        ->required()
                        ->disabled(fn (string $operation): bool => $operation !== 'create'),
                    TextInput::make('external_field')
                        ->label(__('aho.data_integration.fields.external_field'))
                        ->placeholder(__('aho.data_integration.placeholders.external_field'))
                        ->helperText(__('aho.data_integration.help.external_field'))
                        ->required(),
                    Select::make('field_type')
                        ->label(__('aho.data_integration.fields.field_type'))
                        ->options(DataIntegrationFieldMapping::fieldTypeOptions())
                        ->default('direct')
                        ->live()
                        ->required(),
                    Toggle::make('is_required')
                        ->label(__('aho.data_integration.fields.is_required'))
                        ->default(false),
                    Textarea::make('notes')
                        ->label(__('aho.data_integration.fields.notes'))
                        ->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('local_field')
                    ->label(__('aho.data_integration.fields.local_field'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('external_field')
                    ->label(__('aho.data_integration.fields.external_field'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('field_type')
                    ->label(__('aho.data_integration.fields.field_type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('is_required')
                    ->label(__('aho.data_integration.fields.is_required'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no')),
                TextColumn::make('created_at')
                    ->label(__('aho.fields.creation'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('aho.fields.modification'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
