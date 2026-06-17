<?php

namespace App\Filament\Resources\HealthIndicatorValues\Schemas;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Support\ApprovalWorkflow;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HealthIndicatorValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('aho.form_sections.primary_attributes'))
                    ->schema([
                        Select::make('indicator_id')
                            ->label(__('aho.fields.indicator'))
                            ->relationship('indicator', 'afrocode', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with('translations')
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'afrocode')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(Indicator::query(), keyName: 'indicator_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(Indicator::query(), $search, 'indicator_id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('location_id')
                            ->label(__('aho.fields.location'))
                            ->relationship('location', 'code', modifyQueryUsing: fn (Builder $query): Builder => UserCountryAccess::scopeLocations(
                                SelectOptions::orderByDisplayName($query->with('translations'), 'code'),
                            ))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
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
                            ->live()
                            ->preload()
                            ->required(),

                        TextInput::make('start_period')
                            ->label(__('aho.fields.start'))
                            ->numeric()
                            ->required(),

                        TextInput::make('end_period')
                            ->label(__('aho.fields.end'))
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.secondary_attributes'))
                    ->schema([
                        Select::make('categoryoption_id')
                            ->label(__('aho.fields.category_option'))
                            ->relationship('categoryOption', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with(['translations', 'parentCategory.translations'])
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                            ->getOptionLabelFromRecordUsing(fn (IndicatorCategory $record): string => self::categoryOptionLabel($record))
                            ->options(fn (): array => self::categoryOptionOptions())
                            ->getSearchResultsUsing(fn (?string $search): array => self::categoryOptionSearchResults($search))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('datasource_id')
                            ->label(__('aho.fields.source'))
                            ->relationship('dataSource', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with('translations')
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), keyName: 'datasource_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), $search, 'datasource_id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('measuremethod_id')
                            ->label(__('aho.fields.measure_method'))
                            ->relationship('measureMethod', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with('translations')
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), keyName: 'measuremethod_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(MeasureMethod::query(), $search, 'measuremethod_id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make(__('aho.fields.value'))
                    ->schema([
                        TextInput::make('value_received')
                            ->label(__('aho.fields.value_received'))
                            ->numeric()
                            ->live()
                            ->disabled(fn (Get $get): bool => filled($get('string_value')))
                            ->dehydrated()
                            ->required(fn (Get $get): bool => blank($get('string_value')))
                            ->afterStateUpdated(fn (Set $set, mixed $state): mixed => filled($state) ? $set('string_value', null) : null),

                        TextInput::make('numerator_value')
                            ->label(__('aho.fields.numerator'))
                            ->numeric(),

                        TextInput::make('denominator_value')
                            ->label(__('aho.fields.denominator'))
                            ->numeric(),

                        TextInput::make('min_value')
                            ->label(__('aho.fields.min'))
                            ->numeric(),

                        TextInput::make('max_value')
                            ->label(__('aho.fields.max'))
                            ->numeric(),

                        TextInput::make('target_value')
                            ->label(__('aho.fields.target'))
                            ->numeric(),

                        TextInput::make('string_value')
                            ->label(__('aho.fields.text_value'))
                            ->maxLength(500)
                            ->live()
                            ->disabled(fn (Get $get): bool => filled($get('value_received')))
                            ->dehydrated()
                            ->required(fn (Get $get): bool => blank($get('value_received')))
                            ->afterStateUpdated(fn (Set $set, mixed $state): mixed => filled($state) ? $set('value_received', null) : null)
                            ->columnSpanFull(),

                        Toggle::make('priority')
                            ->label(__('aho.fields.priority'))
                            ->disabled(fn (Get $get, ?Model $record): bool => self::priorityLimitReached($get, $record))
                            ->helperText(fn (Get $get, ?Model $record): ?string => self::priorityLimitReached($get, $record)
                                ? __('aho.indicator_values.priority_limit_reached')
                                : null)
                            ->dehydrated(fn (Get $get, ?Model $record): bool => ! self::priorityLimitReached($get, $record))
                            ->default(false),

                        Select::make('comment')
                            ->label(__('aho.fields.approval_status'))
                            ->options(fn (string $operation): array => $operation === 'create'
                                ? self::pendingApprovalOption()
                                : ApprovalWorkflow::options())
                            ->default(ApprovalWorkflow::STATUS_PENDING)
                            ->required(fn (string $operation): bool => $operation === 'create' || self::canApprove())
                            ->disabled(fn (string $operation): bool => $operation === 'create' || ! self::canApprove())
                            ->dehydrated(fn (string $operation): bool => $operation === 'create' || self::canApprove()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function pendingApprovalOption(): array
    {
        return [
            ApprovalWorkflow::STATUS_PENDING => ApprovalWorkflow::label(ApprovalWorkflow::STATUS_PENDING),
        ];
    }

    private static function canApprove(): bool
    {
        return (bool) (
            auth()->user()
            && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE)
        );
    }

    /**
     * @return array<int|string, string|array<int, string>>
     */
    private static function categoryOptionOptions(): array
    {
        return self::categoryOptionRecords()
            ->groupBy(fn (IndicatorCategory $record): string => $record->parentCategory?->display_name ?: __('aho.data_integration.other'))
            ->map(fn ($group): array => $group
                ->mapWithKeys(fn (IndicatorCategory $record): array => [
                    $record->categoryoption_id => $record->display_name,
                ])
                ->all())
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function categoryOptionSearchResults(?string $search = null): array
    {
        $normalizedSearch = self::normalizeCategoryOption((string) $search);

        return self::categoryOptionRecords()
            ->filter(fn (IndicatorCategory $record): bool => blank($search) || str_contains(
                self::normalizeCategoryOption(self::categoryOptionLabel($record)),
                $normalizedSearch,
            ))
            ->mapWithKeys(fn (IndicatorCategory $record): array => [
                $record->categoryoption_id => self::categoryOptionLabel($record),
            ])
            ->all();
    }

    private static function categoryOptionRecords()
    {
        return IndicatorCategory::query()
            ->with(['translations', 'parentCategory.translations'])
            ->limit(SelectOptions::LIMIT)
            ->get()
            ->sortBy(
                fn (IndicatorCategory $record): string => self::normalizeCategoryOption(
                    ($record->parentCategory?->display_name ?? '').' '.$record->display_name,
                ),
                SORT_NATURAL,
            );
    }

    private static function categoryOptionLabel(IndicatorCategory $record): string
    {
        $group = $record->parentCategory?->display_name;
        $option = $record->display_name;

        return filled($group) ? "{$group} - {$option}" : $option;
    }

    private static function normalizeCategoryOption(string $value): string
    {
        return (string) Str::of($value)->ascii()->lower()->squish();
    }

    private static function priorityLimitReached(Get $get, ?Model $record): bool
    {
        $locationId = $get('location_id');

        if (blank($locationId)) {
            return false;
        }

        if ($record instanceof HealthIndicatorValue && (bool) $record->priority) {
            return false;
        }

        return HealthIndicatorValue::query()
            ->where('location_id', $locationId)
            ->where('priority', true)
            ->count() >= 10;
    }
}
