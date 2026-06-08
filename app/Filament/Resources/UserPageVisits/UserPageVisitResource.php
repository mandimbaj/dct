<?php

namespace App\Filament\Resources\UserPageVisits;

use App\Filament\Clusters\Authentication;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\UserPageVisits\Pages\ListUserPageVisits;
use App\Models\UserPageVisit;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserPageVisitResource extends Resource
{
    protected static ?string $model = UserPageVisit::class;

    protected static ?string $cluster = Authentication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $slug = 'user-history';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.menus.authentication');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.user_page_visits.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.user_page_visits.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.user_page_visits.plural');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_super_admin;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('visited_at', 'desc')
            ->columns([
                TextColumn::make('visited_at')->label(__('aho.fields.visited_at'))->dateTime()->sortable(),
                TextColumn::make('user_name')->label(__('aho.fields.user'))->searchable()->sortable(),
                TextColumn::make('user_email')->label(__('aho.fields.email'))->searchable()->toggleable(),
                TextColumn::make('is_super_admin')
                    ->label(__('aho.fields.super_admin'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('aho.fields.yes') : __('aho.fields.no'))
                    ->sortable(),
                TextColumn::make('country_name')->label(__('aho.fields.assigned_country'))->placeholder(__('aho.fields.all_countries'))->searchable()->toggleable(),
                TextColumn::make('country_iso')->label(__('aho.fields.iso_alpha'))->badge()->toggleable(),
                TextColumn::make('page_label')->label(__('aho.fields.page'))->searchable()->wrap(),
                TextColumn::make('path')->label(__('aho.fields.path'))->searchable()->wrap()->toggleable(),
                TextColumn::make('method')->label(__('aho.fields.method'))->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')->label(__('aho.fields.ip_address'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')->label(__('aho.fields.user_agent'))->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('aho.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('country_iso')
                    ->label(__('aho.fields.assigned_country'))
                    ->options(fn (): array => UserPageVisit::query()
                        ->whereNotNull('country_iso')
                        ->distinct()
                        ->orderBy('country_iso')
                        ->pluck('country_iso', 'country_iso')
                        ->all()),
                SelectFilter::make('is_super_admin')
                    ->label(__('aho.fields.super_admin'))
                    ->options([
                        '1' => __('aho.fields.yes'),
                        '0' => __('aho.fields.no'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserPageVisits::route('/'),
        ];
    }
}
