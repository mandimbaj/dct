<?php

namespace App\Filament\Resources\HealthFacilities;

use App\Filament\Clusters\Facilities;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthFacilities\RelationManagers\ServiceAvailabilitiesRelationManager;
use App\Filament\Resources\HealthFacilities\RelationManagers\ServiceCapacitiesRelationManager;
use App\Filament\Resources\HealthFacilities\RelationManagers\ServiceReadinessRelationManager;
use App\Filament\Resources\HealthFacilities\Pages\CreateHealthFacility;
use App\Filament\Resources\HealthFacilities\Pages\EditHealthFacility;
use App\Filament\Resources\HealthFacilities\Pages\ListHealthFacilities;
use App\Models\HealthFacility;
use App\Models\Country;
use App\Models\FacilityOwner;
use App\Models\FacilityType;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\StatusColor;
use App\Support\UserCountryAccess;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Facility master-data resource for the Facilities module.
 *
 * Facility service fact tables point back to these records through the warehouse location and
 * facility reference relationships.
 */
class HealthFacilityResource extends Resource
{
    protected static ?string $model = HealthFacility::class;

    protected static ?string $cluster = Facilities::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'facilities';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_facilities.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_facilities.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_facilities.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'shortname',
            'code',
            'status',
            'location.code',
            'location.translations.name',
            'type.code',
            'type.translations.name',
            'owner.code',
            'owner.translations.name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uuid'),
                Hidden::make('user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make(__('aho.form_sections.health_facility_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('aho.fields.name'))
                            ->required()
                            ->maxLength(230),
                        TextInput::make('shortname')
                            ->label(__('aho.fields.short_name'))
                            ->maxLength(230),
                        Hidden::make('code'),
                        Select::make('type_id')
                            ->label(__('aho.fields.type'))
                            ->relationship('type', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(FacilityType::query(), keyName: 'type_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityType::query(), $search, 'type_id'))
                            ->searchable()
                            ->required(),
                        Select::make('owner_id')
                            ->label(__('aho.fields.owner'))
                            ->relationship('owner', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(FacilityOwner::query()),
                                keyName: 'owner_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(FacilityOwner::query()),
                                $search,
                                'owner_id',
                            ))
                            ->searchable()
                            ->required(),
                        Select::make('location_id')
                            ->label(__('aho.fields.location'))
                            ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                keyName: 'location_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()),
                                $search,
                                'location_id',
                            ))
                            ->searchable()
                            ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                            ->required(),
                        TextInput::make('admin_location')
                            ->label(__('aho.fields.admin_location'))
                            ->maxLength(230),
                        Select::make('status')
                            ->label(__('aho.fields.status'))
                            ->options([
                                'active' => __('aho.status.active'),
                                'closed' => __('aho.status.closed'),
                            ])
                            ->default('active')
                            ->required(),
                        Textarea::make('description')
                            ->label(__('aho.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('aho.form_sections.geolocation_contact'))
                    ->schema([
                        TextInput::make('latitude')
                            ->label(__('aho.fields.latitude'))
                            ->numeric(),
                        TextInput::make('longitude')
                            ->label(__('aho.fields.longitude'))
                            ->numeric(),
                        TextInput::make('altitude')
                            ->label(__('aho.fields.altitude'))
                            ->numeric(),
                        TextInput::make('geosource')
                            ->label(__('aho.fields.geosource'))
                            ->maxLength(500),
                        TextInput::make('address')
                            ->label(__('aho.fields.address'))
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label(__('aho.fields.email'))
                            ->email()
                            ->maxLength(250),
                        TextInput::make('phone_code')
                            ->label(__('aho.fields.phone_code'))
                            ->default('')
                            ->maxLength(5),
                        TextInput::make('phone_part')
                            ->label(__('aho.fields.phone_part'))
                            ->default('')
                            ->maxLength(15),
                        TextInput::make('url')
                            ->label(__('aho.fields.url'))
                            ->url()
                            ->maxLength(2083)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('facility_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['name', 'shortname', 'code', 'status'],
                    relations: [
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'type' => ['code'],
                        'type.translations' => ['name'],
                        'owner' => ['code'],
                        'owner.translations' => ['name'],
                    ],
                    numericColumns: ['facility_id'],
                );
            })
            ->columns([
                TextColumn::make('facility_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('display_name')->label(__('aho.fields.facility'))->searchable(['name', 'shortname', 'code'])->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('type.display_name')->label(__('aho.fields.type'))->toggleable(),
                TextColumn::make('owner.display_name')->label(__('aho.fields.owner'))->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('status')
                    ->label(__('aho.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => [
                        'active' => __('aho.status.active'),
                        'closed' => __('aho.status.closed'),
                    ][$state] ?? (string) $state)
                    ->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                CountryTableFilter::make(),
                SelectFilter::make('type_id')
                    ->label(__('aho.fields.type'))
                    ->relationship('type', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(FacilityType::query(), $search, 'type_id'))
                    ->searchable(),
                SelectFilter::make('owner_id')
                    ->label(__('aho.fields.owner'))
                    ->relationship('owner', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                        SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                    ))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(FacilityOwner::query()),
                        $search,
                        'owner_id',
                    ))
                    ->searchable(),
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
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with(['location.translations', 'type.translations', 'owner.translations']),
            'location_id',
        );
    }

    public static function getRelations(): array
    {
        return [
            ServiceAvailabilitiesRelationManager::class,
            ServiceCapacitiesRelationManager::class,
            ServiceReadinessRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthFacilities::route('/'),
            'create' => CreateHealthFacility::route('/create'),
            'edit' => EditHealthFacility::route('/{record}/edit'),
        ];
    }
}
