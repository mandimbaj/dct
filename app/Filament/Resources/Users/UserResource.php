<?php

namespace App\Filament\Resources\Users;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = Authentication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.menus.authentication');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.users.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.users.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.users.plural');
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()
            && UserPermissions::allowsResource(Auth::user(), static::class, UserPermissions::ACTION_VIEW);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return (bool) $user
            && ($user->canViewAllCountries() || filled($user->location_id))
            && UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_CREATE);
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user || ! UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_UPDATE)) {
            return false;
        }

        return static::recordIsManageableBy($record, $user);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return (bool) $user
            && UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_DELETE)
            && static::recordIsManageableBy($record, $user);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('aho.form_sections.primary_attributes'))
                    ->description(__('aho.auth_management.help.user_identity'))
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('aho.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name'),
                        TextInput::make('email')
                            ->label(__('aho.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->autocomplete('email'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label(__('aho.fields.password'))
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->maxLength(255)
                                    ->autocomplete('new-password')
                                    ->helperText(__('aho.auth_management.help.password_policy'))
                                    ->rule(Password::min(12)->mixedCase()->numbers()->symbols(), fn (?string $state): bool => filled($state)),
                                TextInput::make('password_confirmation')
                                    ->label(__('aho.fields.password_confirmation'))
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->autocomplete('new-password')
                                    ->same('password')
                                    ->required(fn (string $operation, ?string $state, ?string $rawState): bool => $operation === 'create' || filled($rawState)),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('aho.auth_management.sections.user_access'))
                    ->description(__('aho.auth_management.help.user_access'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Checkbox::make('is_super_admin')
                            ->label(__('aho.fields.super_admin'))
                            ->helperText(__('aho.auth_management.help.super_admin'))
                            ->disabled(fn (): bool => ! Auth::user()?->canViewAllCountries())
                            ->dehydrated(fn (): bool => (bool) Auth::user()?->canViewAllCountries()),
                        Select::make('location_id')
                            ->label(__('aho.fields.assigned_country'))
                            ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query
                                    ->where('locationlevel_id', 2)
                                    ->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()->where('locationlevel_id', 2)),
                                keyName: 'location_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()->where('locationlevel_id', 2)),
                                $search,
                                'location_id',
                            ))
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => Auth::user()?->canViewAllCountries() ? null : Auth::user()?->location_id)
                            ->disabled(fn (): bool => ! Auth::user()?->canViewAllCountries())
                            ->dehydrated(fn (): bool => (bool) Auth::user()?->canViewAllCountries())
                            ->helperText(__('aho.help.assigned_country')),
                        Select::make('role_id')
                            ->label(__('aho.fields.role'))
                            ->options(fn (): array => Role::query()
                                ->with('location.translations')
                                ->orderBy('name')
                                ->get()
                                ->filter(fn (Role $role): bool => Auth::user()?->canAssignRole($role) ?? false)
                                ->mapWithKeys(fn (Role $role): array => [$role->getKey() => $role->displayName()])
                                ->sortBy(fn (string $label): string => mb_strtolower($label))
                                ->all())
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::filterAndSort(
                                Role::query()
                                    ->with('location.translations')
                                    ->orderBy('name')
                                    ->get()
                                    ->filter(fn (Role $role): bool => Auth::user()?->canAssignRole($role) ?? false)
                                    ->mapWithKeys(fn (Role $role): array => [$role->getKey() => $role->displayName()])
                                    ->all(),
                                $search,
                            ))
                            ->searchable()
                            ->preload()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(__('aho.help.role')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('aho.fields.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('aho.fields.email'))->searchable()->sortable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.assigned_country'))->placeholder(__('aho.fields.all_countries'))->toggleable(),
                TextColumn::make('role.name')->label(__('aho.fields.role'))->placeholder(__('aho.fields.no_role'))->searchable()->sortable()->toggleable(),
                TextColumn::make('location_assignments_count')
                    ->label(__('aho.fields.level2_locations'))
                    ->counts('locationAssignments')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_super_admin')->label(__('aho.fields.super_admin'))->badge()->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no')),
                TextColumn::make('email_verified_at')->label(__('aho.fields.verified'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('aho.fields.creation'))->dateTime()->sortable(),
                TextColumn::make('updated_at')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    private static function recordIsManageableBy(Model $record, User $user): bool
    {
        if ($user->canViewAllCountries()) {
            return true;
        }

        return ! $record->is_super_admin
            && (int) $record->location_id === (int) $user->location_id;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->canViewAllCountries()) {
            return $query->with(['location.translations', 'role']);
        }

        if (
            $user
            && filled($user->location_id)
            && UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_VIEW)
        ) {
            return $query
                ->with(['location.translations'])
                ->with('role')
                ->where('location_id', $user->location_id)
                ->where('is_super_admin', false);
        }

        return $query->whereRaw('1 = 0');
    }
}
