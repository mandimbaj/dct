<?php

namespace App\Filament\Resources\RecurringEvents;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthWorkforceValues\HealthWorkforceValueResource;
use App\Filament\Resources\RecurringEvents\Pages\CreateRecurringEvent;
use App\Filament\Resources\RecurringEvents\Pages\EditRecurringEvent;
use App\Filament\Resources\RecurringEvents\Pages\ListRecurringEvents;
use App\Models\Country;
use App\Models\HealthCadre;
use App\Models\RecurringEvent;
use App\Support\CountryTableFilter;
use App\Support\SelectOptions;
use App\Support\StatusColor;
use App\Support\TranslatedReferenceForm;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Data submenu for recurring workforce events.
 *
 * Django labelled this area "Nursing & Midwifery"; the Laravel slug keeps that user-facing meaning
 * while the model maps to stg_recurring_event.
 */
class RecurringEventResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = RecurringEvent::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Data';

    protected static ?string $slug = 'nursing-midwifery';

    protected static ?int $navigationSort = 3;

    /**
     * Existing country roles with Health workforce values access should see related event data.
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
        return __('aho.resources.recurring_events.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.recurring_events.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.recurring_events.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: static::getModel(),
            translationFields: ['name', 'shortname', 'theme'],
            baseComponents: [
                TextInput::make('start_year')
                    ->label(__('aho.fields.start'))
                    ->numeric()
                    ->minValue(1900)
                    ->required(),
                TextInput::make('end_year')
                    ->label(__('aho.fields.end'))
                    ->numeric()
                    ->minValue(1900)
                    ->rules(['gte:start_year'])
                    ->required(),
                Select::make('status')
                    ->label(__('aho.fields.status'))
                    ->options(self::statusOptions())
                    ->default('active')
                    ->required(),
                Select::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()),
                        keyName: 'location_id',
                    ))
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()),
                        $search,
                        'location_id',
                    ))
                    ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                    ->searchable()
                    ->required(),
                Select::make('cadres')
                    ->label(__('aho.fields.cadre'))
                    ->relationship('cadres', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                    ->getOptionLabelFromRecordUsing(fn (HealthCadre $record): string => $record->display_name)
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('internal_url')
                    ->label(__('aho.fields.internal_url'))
                    ->maxLength(100),
                TextInput::make('external_url')
                    ->label(__('aho.fields.external_url'))
                    ->url()
                    ->maxLength(2083),
                TextInput::make('cover_image')
                    ->label(__('aho.fields.cover_image'))
                    ->maxLength(100),
            ],
            includeIdentityComponents: false,
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_id', 'desc')
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.name'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
                TextColumn::make('period')->label(__('aho.fields.period'))->sortable()->searchable(),
                TextColumn::make('status')
                    ->label(__('aho.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('display_theme')->label(__('aho.fields.theme'))->limit(60)->toggleable(),
                TextColumn::make('cadres_count')->label(__('aho.fields.cadres_count'))->counts('cadres')->sortable()->toggleable(),
                TextColumn::make('external_url')->label(__('aho.fields.external_url'))->url(fn (RecurringEvent $record): ?string => filled($record->external_url) ? $record->external_url : null)->openUrlInNewTab()->toggleable(isToggledHiddenByDefault: true),
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
        // Country scoping protects country users while super admins keep the regional view.
        return UserCountryAccess::scope(
            parent::getEloquentQuery()
                ->with(['translations', 'location.translations'])
                ->withCount('cadres'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecurringEvents::route('/'),
            'create' => CreateRecurringEvent::route('/create'),
            'edit' => EditRecurringEvent::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'active' => __('aho.status.active'),
            'inactive' => __('aho.status.inactive'),
            'suspended' => __('aho.status.suspended'),
        ];
    }
}
