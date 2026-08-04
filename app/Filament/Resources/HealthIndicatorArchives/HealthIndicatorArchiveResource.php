<?php

namespace App\Filament\Resources\HealthIndicatorArchives;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthIndicatorArchives\Pages\EditHealthIndicatorArchive;
use App\Filament\Resources\HealthIndicatorArchives\Pages\ListHealthIndicatorArchives;
use App\Filament\Resources\HealthIndicatorArchives\Schemas\HealthIndicatorArchiveForm;
use App\Filament\Resources\HealthIndicatorArchives\Tables\HealthIndicatorArchivesTable;
use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\HealthIndicatorArchive;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HealthIndicatorArchiveResource extends Resource
{
    protected static ?string $model = HealthIndicatorArchive::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'archives';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'fact_id';

    protected static bool $isGloballySearchable = false;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_archives.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_archives.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_archives.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) $user && (
            UserPermissions::allowsResource($user, static::class, UserPermissions::ACTION_VIEW)
            || UserPermissions::allowsResource($user, HealthIndicatorValueResource::class, UserPermissions::ACTION_VIEW)
        );
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->is_super_admin;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'uuid',
            'period',
            'comment',
            'string_value',
            'indicator.afrocode',
            'indicator.gen_code',
            'indicator.translations.name',
            'indicator.translations.shortname',
            'location.code',
            'location.iso_alpha',
            'location.translations.name',
            'categoryOption.code',
            'categoryOption.translations.name',
            'dataSource.code',
            'dataSource.translations.name',
            'measureMethod.code',
            'measureMethod.translations.name',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return trim('#'.$record->fact_id.' '.($record->indicator?->afrocode ?? ''));
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('aho.fields.indicator') => $record->indicator?->display_name,
            __('aho.fields.location') => $record->location?->display_name,
            __('aho.fields.period') => $record->period,
            __('aho.fields.value_received') => (string) $record->value_received,
            __('aho.fields.approval_status') => ApprovalWorkflow::label($record->comment),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return HealthIndicatorArchiveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HealthIndicatorArchivesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(
            parent::getEloquentQuery()->with([
                'indicator.translations',
                'location.translations',
                'categoryOption.translations',
                'dataSource.translations',
                'measureMethod.translations',
                'uploadedBy',
                'activeValue.uploadedBy',
                'activeValue.warehouseUploadedBy',
                'warehouseUploadedBy',
            ]),
        );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthIndicatorArchives::route('/'),
            'edit' => EditHealthIndicatorArchive::route('/{record}/edit'),
        ];
    }
}
