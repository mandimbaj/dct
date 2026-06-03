<?php

namespace App\Filament\Resources\NationalObservatories;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\NationalObservatories\Pages\EditNationalObservatory;
use App\Filament\Resources\NationalObservatories\Pages\CreateNationalObservatory;
use App\Filament\Clusters\Regions;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\NationalObservatories\Pages\ListNationalObservatories;
use App\Models\NationalObservatory;
use App\Support\FilamentSearch;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NationalObservatoryResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = NationalObservatory::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'national-observatories';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.national_observatories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.national_observatories.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.national_observatories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::nationalObservatory($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('observatory_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'email', 'phone_number', 'url'],
                    relations: ['translations' => ['name', 'shortname', 'address']],
                    numericColumns: ['observatory_id'],
                );
            })
            ->columns([
                TextColumn::make('observatory_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('display_name')->label(__('aho.fields.name'))->wrap()->sortable(),
                TextColumn::make('email')->label(__('aho.fields.email'))->toggleable(),
                TextColumn::make('phone_number')->label(__('aho.fields.phone_number'))->toggleable(),
                TextColumn::make('url')->label(__('aho.fields.url'))->wrap()->toggleable(),
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
        return parent::getEloquentQuery()->with('translations');
    }

    protected static function fallbackPermissionResources(): array
    {
        return [CountryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNationalObservatories::route('/'),
            'create' => CreateNationalObservatory::route('/create'),
            'edit' => EditNationalObservatory::route('/{record}/edit'),
        ];
    }
}
