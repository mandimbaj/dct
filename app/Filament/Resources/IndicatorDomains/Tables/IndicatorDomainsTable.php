<?php

namespace App\Filament\Resources\IndicatorDomains\Tables;

use App\Support\FilamentSearch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IndicatorDomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: [
                        'translations' => ['name'],
                        'parent' => ['code'],
                        'parent.translations' => ['name'],
                    ],
                    numericColumns: ['domain_id', 'level'],
                );
            })
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('aho.fields.domain'))
                    ->wrap(),
                TextColumn::make('code')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label(__('aho.fields.level'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('parent.display_name')
                    ->label(__('aho.fields.parent'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('indicators_count')
                    ->label(__('aho.fields.indicators_count'))
                    ->counts('indicators')
                    ->sortable(),
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
                SelectFilter::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
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
