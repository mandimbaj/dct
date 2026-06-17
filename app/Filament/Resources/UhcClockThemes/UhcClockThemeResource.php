<?php

namespace App\Filament\Resources\UhcClockThemes;

use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\UhcClockThemes\Pages\CreateUhcClockTheme;
use App\Filament\Resources\UhcClockThemes\Pages\EditUhcClockTheme;
use App\Filament\Resources\UhcClockThemes\Pages\ListUhcClockThemes;
use App\Models\UhcClockIndicator;
use App\Models\UhcClockIndicatorGroup;
use App\Models\UhcClockTheme;
use App\Support\SelectOptions;
use App\Support\TranslatedReferenceForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UhcClockThemeResource extends Resource
{
    protected static ?string $model = UhcClockTheme::class;

    protected static ?string $cluster = UhcClock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'themes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.uhc_clock_themes.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel(), baseComponents: [
            Select::make('level')
                ->label(__('aho.fields.level'))
                ->options([
                    1 => __('aho.fields.level').' 1',
                    2 => __('aho.fields.level').' 2',
                ])
                ->required(),
            Select::make('group_id')
                ->label(__('aho.fields.group'))
                ->relationship('group', 'group_id', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'group_id'))
                ->getOptionLabelFromRecordUsing(fn (UhcClockIndicatorGroup $record): string => $record->display_name)
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('indicators', [])),
            Select::make('parent_id')
                ->label(__('aho.fields.parent'))
                ->relationship('parent', 'domain_id', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'domain_id'))
                ->getOptionLabelFromRecordUsing(fn (UhcClockTheme $record): string => $record->display_name)
                ->searchable()
                ->preload(),
            Select::make('indicators')
                ->label(__('aho.fields.indicators'))
                ->relationship('indicators', 'id', modifyQueryUsing: function (Builder $query, Get $get): Builder {
                    return $query
                        ->with(['indicator.translations'])
                        ->when(
                            filled($get('group_id')),
                            fn (Builder $query): Builder => $query->where('group_id', $get('group_id')),
                        )
                        ->orderBy('indicator_id');
                })
                ->getOptionLabelFromRecordUsing(fn (UhcClockIndicator $record): string => trim(($record->indicator?->afrocode ? $record->indicator->afrocode.' - ' : '').($record->indicator?->display_name ?? $record->getKey())))
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.theme'))->wrap(),
            TextColumn::make('level')->label(__('aho.fields.level'))->badge()->sortable(),
            TextColumn::make('group.display_name')->label(__('aho.fields.group'))->toggleable(),
            TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->placeholder('-')->toggleable(),
            TextColumn::make('indicators_count')->label(__('aho.fields.indicators_count'))->counts('indicators')->sortable(),
            TextColumn::make('run_id')->label(__('aho.fields.run'))->toggleable(),
            TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
            TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListUhcClockThemes::route('/'),
            'create' => CreateUhcClockTheme::route('/create'),
            'edit' => EditUhcClockTheme::route('/{record}/edit'),
        ];
    }
}
