<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Models\LocationLevel;
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

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'iso_alpha', 'iso_number'],
                    relations: [
                        'translations' => ['name'],
                        'parent' => ['code'],
                        'parent.translations' => ['name'],
                        'locationLevel' => ['code'],
                        'locationLevel.translations' => ['name'],
                    ],
                    numericColumns: ['location_id', 'locationlevel_id'],
                );
            })
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('aho.fields.location')),
                TextColumn::make('code')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('iso_alpha')
                    ->label(__('aho.fields.iso_alpha'))
                    ->searchable(),
                TextColumn::make('iso_number')
                    ->label(__('aho.fields.iso_no'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parent.display_name')
                    ->label(__('aho.fields.parent'))
                    ->toggleable(),
                TextColumn::make('locationLevel.display_name')
                    ->label(__('aho.fields.level'))
                    ->placeholder('-'),
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
                SelectFilter::make('locationlevel_id')
                    ->label(__('aho.fields.level'))
                    ->relationship('locationLevel', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(LocationLevel::query(), $search, 'locationlevel_id'))
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
