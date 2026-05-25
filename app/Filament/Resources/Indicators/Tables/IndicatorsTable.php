<?php

namespace App\Filament\Resources\Indicators\Tables;

use App\Models\IndicatorReference;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IndicatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['afrocode', 'gen_code'],
                    relations: [
                        'translations' => ['name', 'shortname', 'definition'],
                        'reference' => ['code'],
                        'reference.translations' => ['name'],
                    ],
                    numericColumns: ['indicator_id'],
                );
            })
            ->columns([
                TextColumn::make('afrocode')
                    ->label(__('aho.fields.afro_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label(__('aho.fields.indicator'))
                    ->wrap(),
                TextColumn::make('gen_code')
                    ->label(__('aho.fields.general_code'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reference.code')
                    ->label(__('aho.fields.reference'))
                    ->searchable()
                    ->toggleable(),
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
                SelectFilter::make('reference_id')
                    ->label(__('aho.fields.reference'))
                    ->relationship('reference', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(IndicatorReference::query(), $search, 'reference_id'))
                    ->searchable()
                    ->preload(),
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
}
