<?php

namespace App\Filament\Resources\HealthIndicatorValues\Tables;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\HealthIndicatorValue;
use App\Support\ApprovalWorkflow;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HealthIndicatorValuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fact_id', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['period', 'comment'],
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
                    numericColumns: ['fact_id', 'value_received', 'numerator_value', 'denominator_value', 'target_value'],
                );
            })
            ->columns([
                TextColumn::make('fact_id')
                    ->label(__('aho.fields.id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('indicator.afrocode')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('indicator.display_name')
                    ->label(__('aho.fields.indicator'))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('location.display_name')
                    ->label(__('aho.fields.location'))
                    ->toggleable(),
                TextColumn::make('period')
                    ->label(__('aho.fields.period'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoryOption.display_name')
                    ->label(__('aho.fields.disaggregation'))
                    ->toggleable(),
                TextColumn::make('dataSource.display_name')
                    ->label(__('aho.fields.source'))
                    ->toggleable(),
                TextColumn::make('measureMethod.display_name')
                    ->label(__('aho.fields.method'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('value_received')
                    ->label(__('aho.fields.value_received'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->badge()
                    ->color(fn (?string $state): string => ApprovalWorkflow::color($state))
                    ->formatStateUsing(fn (?string $state): string => ApprovalWorkflow::label($state))
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('aho.fields.approved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('target_value')
                    ->label(__('aho.fields.target'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_created')
                    ->label(__('aho.fields.creation'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('date_lastupdated')
                    ->label(__('aho.fields.modification'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->afrocode ? "{$record->afrocode} - " : '').$record->display_name))
                    ->searchable(),
                CountryTableFilter::make(),
                SelectFilter::make('datasource_id')
                    ->label(__('aho.fields.source'))
                    ->relationship('dataSource', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable(),
                SelectFilter::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('aho.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (HealthIndicatorValue $record): bool => ! ApprovalWorkflow::isApproved($record)
                        && (bool) auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE))
                    ->action(function (HealthIndicatorValue $record): void {
                        ApprovalWorkflow::approve($record);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
