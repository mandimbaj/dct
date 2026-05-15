<?php

namespace App\Filament\Resources\FacilityOwners;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\FacilityOwners\Pages\CreateFacilityOwner;
use App\Filament\Resources\FacilityOwners\Pages\EditFacilityOwner;
use App\Filament\Resources\FacilityOwners\Pages\ListFacilityOwners;
use App\Models\FacilityOwner;
use App\Support\UserCountryAccess;
use App\Support\WarehouseForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FacilityOwnerResource extends Resource
{
    protected static ?string $model = FacilityOwner::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'owners';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.facility_owners.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.facility_owners.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.facility_owners.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.owner'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
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

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(parent::getEloquentQuery(), 'location_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacilityOwners::route('/'),
            'create' => CreateFacilityOwner::route('/create'),
            'edit' => EditFacilityOwner::route('/{record}/edit'),
        ];
    }
}
