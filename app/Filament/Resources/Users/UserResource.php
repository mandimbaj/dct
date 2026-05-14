<?php

namespace App\Filament\Resources\Users;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        return (bool) (auth()->user()?->canViewAllCountries() || auth()->user()?->is_country_admin);
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()?->canViewAllCountries()) {
            return true;
        }

        return (bool) auth()->user()?->is_country_admin
            && ! $record->is_super_admin
            && ! $record->is_country_admin
            && (int) $record->location_id === (int) auth()->user()?->location_id;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('aho.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('aho.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label(__('aho.fields.password'))
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Checkbox::make('is_super_admin')
                    ->label(__('aho.fields.super_admin'))
                    ->disabled(fn (): bool => ! auth()->user()?->canViewAllCountries())
                    ->dehydrated(fn (): bool => (bool) auth()->user()?->canViewAllCountries()),
                Checkbox::make('is_country_admin')
                    ->label(__('aho.fields.country_admin'))
                    ->helperText(__('aho.help.country_admin'))
                    ->disabled(fn (): bool => ! auth()->user()?->canViewAllCountries())
                    ->dehydrated(fn (): bool => (bool) auth()->user()?->canViewAllCountries()),
                Select::make('location_id')
                    ->label(__('aho.fields.assigned_country'))
                    ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('locationlevel_id', 2)
                        ->with('translations'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->default(fn (): ?int => auth()->user()?->canViewAllCountries() ? null : auth()->user()?->location_id)
                    ->disabled(fn (): bool => ! auth()->user()?->canViewAllCountries())
                    ->dehydrated(fn (): bool => (bool) auth()->user()?->canViewAllCountries())
                    ->helperText(__('aho.help.assigned_country')),
                Section::make(__('aho.permissions.section'))
                    ->description(__('aho.permissions.section_help'))
                    ->schema(UserPermissions::formFields())
                    ->columns(1),
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
                TextColumn::make('location_assignments_count')
                    ->label(__('aho.fields.level2_locations'))
                    ->counts('locationAssignments')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_super_admin')->label(__('aho.fields.super_admin'))->badge()->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no')),
                TextColumn::make('is_country_admin')->label(__('aho.fields.country_admin'))->badge()->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no')),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->canViewAllCountries()) {
            return $query->with(['location.translations']);
        }

        if ($user?->is_country_admin && filled($user->location_id)) {
            return $query
                ->with(['location.translations'])
                ->where('location_id', $user->location_id)
                ->where('is_super_admin', false)
                ->where('is_country_admin', false);
        }

        return $query->whereRaw('1 = 0');
    }
}
