<?php

namespace App\Filament\Resources\HealthIndicatorValues\Schemas;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\Country;
use App\Models\DataSource;
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
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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

                        TextInput::make('period')
                            ->label(__('aho.fields.period'))
                            ->maxLength(25)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('aho.form_sections.secondary_attributes'))
                    ->schema([
                        Select::make('categoryoption_id')
                            ->label(__('aho.fields.category_option'))
                            ->relationship('categoryOption', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->with('translations')
                                ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(IndicatorCategory::query(), keyName: 'categoryoption_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(IndicatorCategory::query(), $search, 'categoryoption_id'))
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
                            ->required(),

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
                            ->columnSpanFull(),

                        Toggle::make('priority')
                            ->label(__('aho.fields.priority'))
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
}
