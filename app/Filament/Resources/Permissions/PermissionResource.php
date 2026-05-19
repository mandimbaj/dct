<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Permissions\Pages\EditPermission;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Models\User;
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
        return (bool) auth()->user()?->canViewAllCountries();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()?->canViewAllCountries()) {
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                Select::make('location_id')
                    ->label(__('aho.fields.assigned_country'))
                    ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('locationlevel_id', 2)
                        ->with('translations'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->disabled()
                    ->dehydrated(false),
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
                TextColumn::make('location.display_name')->label(__('aho.fields.assigned_country'))->placeholder(__('aho.fields.all_countries')),
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

        if ($user?->canViewAllCountries()) {
            return $query->with(['location.translations']);
        }

        return $query->whereRaw('1 = 0');
    }
}
