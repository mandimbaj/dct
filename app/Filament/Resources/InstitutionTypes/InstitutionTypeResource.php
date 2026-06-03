<?php

namespace App\Filament\Resources\InstitutionTypes;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthCadres\HealthCadreResource;
use App\Filament\Resources\InstitutionTypes\Pages\CreateInstitutionType;
use App\Filament\Resources\InstitutionTypes\Pages\EditInstitutionType;
use App\Filament\Resources\InstitutionTypes\Pages\ListInstitutionTypes;
use App\Filament\Resources\TrainingInstitutions\TrainingInstitutionResource;
use App\Models\InstitutionType;
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
 * Reference submenu for institution types imported from Django's StgInstitutionType model.
 *
 * The records live in stg_institution_type and are shown under Health workforce > References.
 */
class InstitutionTypeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = InstitutionType::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'institution-types';

    protected static ?int $navigationSort = 4;

    /**
     * Let existing roles that can manage workforce reference data see this new submenu.
     */
    protected static function fallbackPermissionResources(): array
    {
        return [
            HealthCadreResource::class,
            TrainingInstitutionResource::class,
        ];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.institution_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.institution_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.institution_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->label(__('aho.fields.name'))->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable(),
                TextColumn::make('display_shortname')->label(__('aho.fields.short_name'))->toggleable(),
                TextColumn::make('training_institutions_count')->label(__('aho.fields.institutions_count'))->counts('trainingInstitutions')->sortable(),
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
        // Preload translations and counts so the table avoids per-row lookup queries.
        return parent::getEloquentQuery()
            ->with('translations')
            ->withCount('trainingInstitutions');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstitutionTypes::route('/'),
            'create' => CreateInstitutionType::route('/create'),
            'edit' => EditInstitutionType::route('/{record}/edit'),
        ];
    }
}
