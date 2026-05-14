<?php

namespace App\Filament\Resources\FacilityTypes;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\FacilityTypes\Pages\CreateFacilityType;
use App\Filament\Resources\FacilityTypes\Pages\EditFacilityType;
use App\Filament\Resources\FacilityTypes\Pages\ListFacilityTypes;
use App\Models\FacilityType;
use App\Support\WarehouseForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FacilityTypeResource extends Resource
{
    protected static ?string $model = FacilityType::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'types';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.facility_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.facility_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.facility_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.type'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('facilities_count')->label(__('aho.fields.facilities_count'))->counts('facilities')->sortable(),
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
            'index' => ListFacilityTypes::route('/'),
            'create' => CreateFacilityType::route('/create'),
            'edit' => EditFacilityType::route('/{record}/edit'),
        ];
    }
}
