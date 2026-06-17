<?php

namespace App\Filament\Resources\HealthServiceProgrammes;

use App\Filament\Resources\HealthServiceProgrammes\Pages\EditHealthServiceProgramme;
use App\Filament\Resources\HealthServiceProgrammes\Pages\CreateHealthServiceProgramme;
use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthServiceProgrammes\Pages\ListHealthServiceProgrammes;
use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Models\HealthServiceProgramme;
use App\Support\FilamentSearch;
use App\Support\TranslatedReferenceForm;
use App\Support\UserPermissions;
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
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HealthServiceProgrammeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = HealthServiceProgramme::class;

    protected static ?string $cluster = HealthServices::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'hsc-programmes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_service_programmes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_service_programmes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_service_programmes.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::healthServiceProgramme($schema, static::getModel());
    }

    public static function canCreate(): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_CREATE);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_UPDATE);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_DELETE);
    }

    public static function canDeleteAny(): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_DELETE);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('domain_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'level'],
                    relations: [
                        'translations' => ['name', 'shortname', 'description'],
                        'parent.translations' => ['name'],
                    ],
                    numericColumns: ['domain_id', 'level'],
                );
            })
            ->columns([
                TextColumn::make('domain_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('display_name')->label(__('aho.fields.programme'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->sortable()->toggleable(),
                TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->wrap()->toggleable(),
                TextColumn::make('level')->label(__('aho.fields.level'))->sortable(),
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
        return parent::getEloquentQuery()->with(['translations', 'parent.translations']);
    }

    protected static function fallbackPermissionResources(): array
    {
        return [HealthServiceValueResource::class];
    }

    private static function canUseInheritedPermission(string $action): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        foreach ([static::class, ...static::fallbackPermissionResources()] as $resourceClass) {
            if (UserPermissions::allowsResource($user, $resourceClass, $action)) {
                return true;
            }
        }

        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthServiceProgrammes::route('/'),
            'create' => CreateHealthServiceProgramme::route('/create'),
            'edit' => EditHealthServiceProgramme::route('/{record}/edit'),
        ];
    }
}
