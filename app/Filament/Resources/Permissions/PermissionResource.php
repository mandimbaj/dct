<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Permissions\Pages\EditPermission;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Models\Country;
use App\Models\User;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PermissionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = Authentication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $slug = 'permissions';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.menus.authentication');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.permissions.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.permissions.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.permissions.plural');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_super_admin;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()?->is_super_admin) {
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('aho.auth_management.sections.permission_target'))
                    ->description(__('aho.auth_management.help.permission_target'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('aho.fields.name'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('email')
                            ->label(__('aho.fields.email'))
                            ->disabled()
                            ->dehydrated(false),
                        Checkbox::make('is_super_admin')
                            ->label(__('aho.fields.super_admin'))
                            ->disabled()
                            ->dehydrated(false),
                        Checkbox::make('can_view_all_countries')
                            ->label(__('aho.fields.regional_data_access'))
                            ->disabled()
                            ->dehydrated(false),
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
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('aho.permissions.section'))
                    ->description(__('aho.permissions.section_help'))
                    ->icon('heroicon-o-lock-closed')
                    ->schema(UserPermissions::formFields())
                    ->columns(1)
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
                TextColumn::make('location.display_name')->label(__('aho.fields.assigned_country'))->placeholder(__('aho.fields.all_countries')),
                TextColumn::make('can_view_all_countries')
                    ->label(__('aho.fields.regional_data_access'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no'))
                    ->toggleable(),
                TextColumn::make('location_assignments_count')
                    ->label(__('aho.fields.level2_locations'))
                    ->counts('locationAssignments')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_super_admin')
                    ->label(__('aho.fields.super_admin'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no')),
                TextColumn::make('menu_permissions')
                    ->label(__('aho.fields.permissions'))
                    ->badge()
                    ->formatStateUsing(function (mixed $state, User $record): string {
                        if ($record->is_super_admin) {
                            return __('aho.permissions.all_permissions');
                        }

                        $permissions = UserPermissions::normalize($state ?? []);
                        $count = count($permissions[UserPermissions::ACTION_VIEW] ?? []);

                        return trans_choice('aho.permissions.assigned_count', $count, ['count' => $count]);
                    }),
                TextColumn::make('created_at')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->is_super_admin) {
            return $query->with(['location.translations']);
        }

        return $query->whereRaw('1 = 0');
    }
}
