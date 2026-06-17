<?php

namespace App\Filament\Resources\HealthCadres;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\HealthCadres\Pages\CreateHealthCadre;
use App\Filament\Resources\HealthCadres\Pages\EditHealthCadre;
use App\Filament\Resources\HealthCadres\Pages\ListHealthCadres;
use App\Models\HealthCadre;
use App\Support\SelectOptions;
use App\Support\TranslatedReferenceForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HealthCadreResource extends Resource
{
    protected static ?string $model = HealthCadre::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'cadres';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_cadres.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_cadres.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_cadres.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: static::getModel(),
            translationFields: ['name', 'shortname', 'academic', 'description'],
            baseComponents: [
                Select::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query->with('translations'), 'code'))
                    ->getOptionLabelFromRecordUsing(fn (HealthCadre $record): string => $record->display_name)
                    ->options(fn (): array => SelectOptions::fromDisplayNameQuery(HealthCadre::query(), keyName: 'cadre_id'))
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(HealthCadre::query(), $search, 'cadre_id'))
                    ->searchable()
                    ->preload(),
            ],
        );
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.cadre'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('parent.display_name')->label(__('aho.fields.parent'))->placeholder('-')->toggleable(),
            TextColumn::make('workforce_values_count')->label(__('aho.fields.values_count'))->counts('workforceValues')->sortable(),
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
            'index' => ListHealthCadres::route('/'),
            'create' => CreateHealthCadre::route('/create'),
            'edit' => EditHealthCadre::route('/{record}/edit'),
        ];
    }
}
