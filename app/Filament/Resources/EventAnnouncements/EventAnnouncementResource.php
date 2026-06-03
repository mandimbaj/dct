<?php

namespace App\Filament\Resources\EventAnnouncements;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\EventAnnouncements\Pages\CreateEventAnnouncement;
use App\Filament\Resources\EventAnnouncements\Pages\EditEventAnnouncement;
use App\Filament\Resources\EventAnnouncements\Pages\ListEventAnnouncements;
use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use App\Models\EventAnnouncement;
use App\Support\CountryTableFilter;
use App\Support\UserCountryAccess;
use App\Support\WarehouseForm;
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
use UnitEnum;

/**
 * Data submenu for workforce event announcements from stg_event_announcement.
 *
 * Announcements are country-scoped and use translation rows for title and message text.
 */
class EventAnnouncementResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = EventAnnouncement::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'announcements';

    protected static ?int $navigationSort = 4;

    /**
     * Use Health workforce values as the parent permission for this newly exposed data submenu.
     */
    protected static function fallbackPermissionResources(): array
    {
        return [
            HealthWorkforceValueResource::class,
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.data');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.event_announcements.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.event_announcements.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.event_announcements.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_id', 'desc')
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.title'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('status')->label(__('aho.fields.status'))->badge()->sortable(),
                TextColumn::make('display_message')->label(__('aho.fields.message'))->limit(70)->toggleable(),
                TextColumn::make('external_url')->label(__('aho.fields.external_url'))->url(fn (EventAnnouncement $record): ?string => filled($record->external_url) ? $record->external_url : null)->openUrlInNewTab()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                CountryTableFilter::make(),
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
        // Keep list results inside the current country context unless the user is global.
        return UserCountryAccess::scope(
            parent::getEloquentQuery()
                ->with(['translations', 'location.translations']),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventAnnouncements::route('/'),
            'create' => CreateEventAnnouncement::route('/create'),
            'edit' => EditEventAnnouncement::route('/{record}/edit'),
        ];
    }
}
