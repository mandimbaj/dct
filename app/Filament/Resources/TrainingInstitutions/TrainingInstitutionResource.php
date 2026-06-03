<?php

namespace App\Filament\Resources\TrainingInstitutions;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\TrainingInstitutions\Pages\CreateTrainingInstitution;
use App\Filament\Resources\TrainingInstitutions\Pages\EditTrainingInstitution;
use App\Filament\Resources\TrainingInstitutions\Pages\ListTrainingInstitutions;
use App\Models\TrainingInstitution;
use App\Support\UserCountryAccess;
use App\Support\WarehouseForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Health workforce reference submenu for training institutions.
 *
 * The table now exposes institution type and programme count so it connects with the
 * Institution types and Training programmes submenus.
 */
class TrainingInstitutionResource extends Resource
{
    protected static ?string $model = TrainingInstitution::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'training-institutions';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.training_institutions.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.training_institutions.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.training_institutions.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('display_name')->label(__('aho.fields.institution'))->wrap(),
            TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
            TextColumn::make('location.display_name')->label(__('aho.fields.location'))->toggleable(),
            TextColumn::make('type.display_name')->label(__('aho.fields.type'))->toggleable(),
            TextColumn::make('programmes_count')->label(__('aho.fields.programmes_count'))->counts('programmes')->sortable()->toggleable(),
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

    public static function getEloquentQuery(): Builder
    {
        // Load type/programme context used by the expanded Health workforce reference table.
        return UserCountryAccess::scope(
            parent::getEloquentQuery()
                ->with(['translations', 'location.translations', 'type.translations'])
                ->withCount('programmes'),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingInstitutions::route('/'),
            'create' => CreateTrainingInstitution::route('/create'),
            'edit' => EditTrainingInstitution::route('/{record}/edit'),
        ];
    }
}
