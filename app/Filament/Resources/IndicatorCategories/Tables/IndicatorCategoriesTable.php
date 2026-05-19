<?php

namespace App\Filament\Resources\IndicatorCategories\Tables;

use App\Support\FilamentSearch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IndicatorCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code'],
                    relations: ['translations' => ['name']],
                    numericColumns: ['categoryoption_id', 'category_id'],
                );
            })
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('aho.fields.disaggregation')),
                TextColumn::make('code')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category_id')
                    ->label(__('aho.fields.parent_category'))
                    ->numeric()
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
                //
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
