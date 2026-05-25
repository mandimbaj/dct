<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Country;
use App\Models\Role;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $cluster = Authentication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $slug = 'roles';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.menus.authentication');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.auth_management.role_permissions_navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.roles.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.roles.plural');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()
            && UserPermissions::allowsResource(auth()->user(), static::class, UserPermissions::ACTION_VIEW);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return (bool) $user
            && ($user->canViewAllCountries() || filled($user->location_id))
            && UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_CREATE);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Role
            && (bool) auth()->user()
            && UserPermissions::allowsResource(auth()->user(), static::class, UserPermissions::ACTION_UPDATE)
            && $record->canBeManagedBy(auth()->user());
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Role
            && (bool) auth()->user()
            && UserPermissions::allowsResource(auth()->user(), static::class, UserPermissions::ACTION_DELETE)
            && $record->canBeManagedBy(auth()->user())
            && $record->users()->doesntExist();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('aho.auth_management.sections.role_identity'))
                    ->description(__('aho.auth_management.help.role_identity'))
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('aho.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('aho.auth_management.help.role_name')),
                        Select::make('location_id')
                            ->label(__('aho.fields.role_scope'))
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
                            ->placeholder(__('aho.fields.all_countries'))
                            ->default(fn (): ?int => auth()->user()?->canViewAllCountries() ? null : auth()->user()?->location_id)
                            ->disabled(fn (): bool => ! auth()->user()?->canViewAllCountries())
                            ->dehydrated()
                            ->helperText(__('aho.auth_management.help.role_scope')),
                        Checkbox::make('is_system')
                            ->label(__('aho.fields.system_role'))
                            ->helperText(__('aho.auth_management.help.system_role'))
                            ->disabled(fn (): bool => ! auth()->user()?->canViewAllCountries())
                            ->dehydrated(fn (): bool => (bool) auth()->user()?->canViewAllCountries()),
                        Textarea::make('description')
                            ->label(__('aho.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('aho.permissions.role_section'))
                    ->description(__('aho.permissions.role_section_help'))
                    ->icon('heroicon-o-shield-check')
                    ->schema(UserPermissions::formFields())
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('aho.fields.name'))->searchable()->sortable(),
                TextColumn::make('location.display_name')
                    ->label(__('aho.fields.role_scope'))
                    ->placeholder(__('aho.fields.all_countries'))
                    ->toggleable(),
                TextColumn::make('users_count')
                    ->label(__('aho.fields.users_count'))
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('is_system')
                    ->label(__('aho.fields.system_role'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no'))
                    ->toggleable(),
                TextColumn::make('menu_permissions')
                    ->label(__('aho.fields.permissions'))
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        $permissions = UserPermissions::normalize($state ?? []);
                        $count = count($permissions[UserPermissions::ACTION_VIEW] ?? []);

                        return trans_choice('aho.permissions.assigned_count', $count, ['count' => $count]);
                    }),
                TextColumn::make('created_at')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['location.translations']);
        $user = auth()->user();

        if ($user?->canViewAllCountries()) {
            return $query;
        }

        if (
            $user
            && filled($user->location_id)
            && UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_VIEW)
        ) {
            return $query->where('location_id', $user->location_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
