<?php

namespace App\Filament\Resources\TimePeriods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimePeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('period_id', 'desc')
            ->columns([
                TextColumn::make('period_id')
                    ->label(__('aho.fields.id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('code')
                    ->label(__('aho.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('aho.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shortname')
                    ->label(__('aho.fields.short_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('aho.fields.description'))
                    ->wrap()
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
