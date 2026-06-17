<?php

namespace App\Filament\Resources\DataElementGroups;

use App\Filament\Clusters\DataElements;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DataElementGroups\Pages\CreateDataElementGroup;
use App\Filament\Resources\DataElementGroups\Pages\EditDataElementGroup;
use App\Filament\Resources\DataElementGroups\Pages\ListDataElementGroups;
use App\Models\DataElement;
use App\Models\DataElementGroup;
use App\Support\SelectOptions;
use App\Support\WarehouseLocale;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DataElementGroupResource extends Resource
{
    protected static ?string $model = DataElementGroup::class;

    protected static ?string $cluster = DataElements::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'groups';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_element_groups.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_element_groups.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_element_groups.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('uuid'),
            Hidden::make('code'),
            Hidden::make('translation_language_code')
                ->default(fn (): string => WarehouseLocale::current()),

            Section::make(__('aho.form_sections.data_element_group_attributes'))
                ->schema([
                    TextInput::make('translation_name')
                        ->label(__('aho.fields.name'))
                        ->required()
                        ->maxLength(200),
                    TextInput::make('translation_shortname')
                        ->label(__('aho.fields.short_name'))
                        ->required()
                        ->maxLength(120),
                    Textarea::make('translation_description')
                        ->label(__('aho.fields.description'))
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('aho.form_sections.data_elements_allocation'))
                ->schema([
                    Select::make('dataElements')
                        ->label(__('aho.fields.data_elements'))
                        ->relationship('dataElements', 'code', modifyQueryUsing: fn ($query) => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                        ->getOptionLabelFromRecordUsing(fn (DataElement $record): string => trim(($record->code ? $record->code.' - ' : '').$record->display_name))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.group'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('data_elements_count')->label(__('aho.fields.data_elements_count'))->counts('dataElements')->sortable(),
            TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
            TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
        ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataElementGroups::route('/'),
            'create' => CreateDataElementGroup::route('/create'),
            'edit' => EditDataElementGroup::route('/{record}/edit'),
        ];
    }
}
