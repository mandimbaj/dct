<?php

namespace App\Filament\Resources\HealthIndicatorArchives\Tables;

use App\Models\DataSource;
use App\Models\Indicator;
use App\Support\ApprovalWorkflow;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HealthIndicatorArchivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fact_id', 'desc')
            ->paginationMode(PaginationMode::Simple)
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['uuid', 'period', 'comment', 'string_value'],
                    relations: [
                        'indicator' => ['afrocode', 'gen_code'],
                        'indicator.translations' => ['name', 'shortname', 'definition'],
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'categoryOption' => ['code'],
                        'categoryOption.translations' => ['name'],
                        'dataSource' => ['code'],
                        'dataSource.translations' => ['name'],
                        'measureMethod' => ['code'],
                        'measureMethod.translations' => ['name'],
                    ],
                    numericColumns: [
                        'fact_id',
                        'indicator_id',
                        'location_id',
                        'categoryoption_id',
                        'datasource_id',
                        'measuremethod_id',
                        'start_period',
                        'end_period',
                        'value_received',
                        'numerator_value',
                        'denominator_value',
                        'min_value',
                        'max_value',
                        'target_value',
                        'user_id',
                    ],
                );
            })
            ->columns([
                TextColumn::make('fact_id')
                    ->label(__('aho.fields.id'))
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('indicator.afrocode')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('indicator.display_name')
                    ->label(__('aho.fields.indicator'))
                    ->wrap(),
                TextColumn::make('location.display_name')
                    ->label(__('aho.fields.location')),
                TextColumn::make('start_period')
                    ->label(__('aho.fields.start'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_period')
                    ->label(__('aho.fields.end'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('period')
                    ->label(__('aho.fields.period'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoryOption.display_name')
                    ->label(__('aho.fields.disaggregation')),
                TextColumn::make('dataSource.display_name')
                    ->label(__('aho.fields.source')),
                TextColumn::make('measureMethod.display_name')
                    ->label(__('aho.fields.method'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('value_received')
                    ->label(__('aho.fields.value_received'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numerator_value')
                    ->label(__('aho.fields.numerator'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('denominator_value')
                    ->label(__('aho.fields.denominator'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('min_value')
                    ->label(__('aho.fields.min'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_value')
                    ->label(__('aho.fields.max'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target_value')
                    ->label(__('aho.fields.target'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('string_value')
                    ->label(__('aho.fields.text_value'))
                    ->limit(80)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => ApprovalWorkflow::color($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('aho.fields.user'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_created')
                    ->label(__('aho.fields.creation'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('date_lastupdated')
                    ->label(__('aho.fields.modification'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->with('translations')
                        ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'afrocode')))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(Indicator::query(), $search, 'indicator_id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->optionsLimit(SelectOptions::LIMIT),
                CountryTableFilter::make(),
                SelectFilter::make('datasource_id')
                    ->label(__('aho.fields.source'))
                    ->relationship('dataSource', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->with('translations')
                        ->tap(fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code')))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(DataSource::query(), $search, 'datasource_id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->optionsLimit(SelectOptions::LIMIT),
                SelectFilter::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options())
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->is_super_admin),
            ]);
    }
}
