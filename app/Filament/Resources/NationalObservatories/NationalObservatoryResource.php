<?php

namespace App\Filament\Resources\NationalObservatories;

use App\Filament\Clusters\NationalObservatory as NationalObservatoryCluster;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\NationalObservatories\Pages\CreateNationalObservatory;
use App\Filament\Resources\NationalObservatories\Pages\EditNationalObservatory;
use App\Filament\Resources\NationalObservatories\Pages\ListNationalObservatories;
use App\Filament\Resources\NationalObservatories\Schemas\NationalObservatoryForm;
use App\Models\Country;
use App\Models\LocationCode;
use App\Models\NationalObservatory;
use App\Support\FilamentSearch;
use App\Support\NationalObservatoryNotifier;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class NationalObservatoryResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = NationalObservatory::class;

    protected static ?string $cluster = NationalObservatoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

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
        return NationalObservatoryForm::configure($schema);
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
                    relations: [
                        'translations' => ['name', 'shortname', 'address'],
                        'location.translations' => ['name'],
                    ],
                    numericColumns: ['observatory_id'],
                );
            })
            ->columns([
                TextColumn::make('observatory_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('display_name')->label(__('aho.fields.name'))->wrap()->sortable(),
                TextColumn::make('short_name')->label(__('aho.fields.short_name'))->wrap()->toggleable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.country'))->wrap()->sortable()->toggleable(),
                TextColumn::make('email')->label(__('aho.fields.email'))->toggleable(),
                TextColumn::make('phone_code')->label(__('aho.fields.phone_code'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone_number')->label(__('aho.fields.phone_number'))->toggleable(),
                TextColumn::make('url')->label(__('aho.fields.url'))->wrap()->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label(__('aho.fields.country'))
                    ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations($query->with('translations')))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn (NationalObservatory $record): mixed => NationalObservatoryNotifier::record(
                        NationalObservatoryNotifier::ACTION_DELETED,
                        $record,
                    )),
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
            parent::getEloquentQuery()->with(['translations', 'location.translations']),
            'location_id',
        );
    }

    public static function canCreate(): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_CREATE)
            && static::canCreateForAvailableCountry();
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof NationalObservatory
            && static::canUsePermission(UserPermissions::ACTION_UPDATE)
            && UserCountryAccess::allowsLocationId($record->location_id);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof NationalObservatory
            && static::canUsePermission(UserPermissions::ACTION_DELETE)
            && UserCountryAccess::allowsLocationId($record->location_id);
    }

    public static function canDeleteAny(): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_DELETE);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareCreateData(array $data): array
    {
        $data['user_id'] = auth()->id() ?? ($data['user_id'] ?? 1);

        return self::prepareSaveData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareSaveData(array $data, ?NationalObservatory $record = null): array
    {
        $data = UserCountryAccess::enforceLocationData($data);

        self::validateSingleCountryObservatory($data, $record);

        if (filled($data['location_id'] ?? null)) {
            $data['phone_code'] = NationalObservatory::phoneCodeForLocation($data['location_id']);
        }

        $data['phone_number'] = NationalObservatory::phoneNumber(
            $data['phone_code'] ?? null,
            $data['phone_part'] ?? null,
        );

        return $data;
    }

    public static function canCreateForAvailableCountry(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (! UserCountryAccess::canViewAllCountries()) {
            $locationId = UserCountryAccess::locationId();

            return filled($locationId) && ! NationalObservatory::hasObservatoryForLocation($locationId);
        }

        return self::countriesAvailableForCreate()->exists();
    }

    public static function existingForCurrentUserCountry(): ?NationalObservatory
    {
        if (UserCountryAccess::canViewAllCountries()) {
            return null;
        }

        return NationalObservatory::existingForLocation(UserCountryAccess::locationId());
    }

    private static function countriesAvailableForCreate(): Builder
    {
        return UserCountryAccess::scopeLocations(
            Country::query()
                ->whereIn('location_id', LocationCode::query()->select('location_id'))
                ->whereNotIn('location_id', NationalObservatory::query()
                    ->select('location_id')
                    ->whereNotNull('location_id')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function validateSingleCountryObservatory(array $data, ?NationalObservatory $record = null): void
    {
        $locationId = $data['location_id'] ?? null;

        if (! NationalObservatory::hasObservatoryForLocation($locationId, $record?->getKey())) {
            return;
        }

        throw ValidationException::withMessages([
            'data.location_id' => __('aho.validation.national_observatory_country_unique'),
        ]);
    }

    protected static function fallbackPermissionResources(): array
    {
        return [];
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
