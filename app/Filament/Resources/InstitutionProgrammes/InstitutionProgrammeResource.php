<?php

namespace App\Filament\Resources\InstitutionProgrammes;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\InstitutionProgrammes\Pages\CreateInstitutionProgramme;
use App\Filament\Resources\InstitutionProgrammes\Pages\EditInstitutionProgramme;
use App\Filament\Resources\InstitutionProgrammes\Pages\ListInstitutionProgrammes;
use App\Models\InstitutionProgramme;
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
 * Reference submenu for training programmes imported from Django's StgInstitutionProgrammes model.
 *
 * Programmes are connected to institutions through stg_institution_programs_lookup.
 */
class InstitutionProgrammeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = InstitutionProgramme::class;

    protected static ?string $cluster = HealthWorkforce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'training-programmes';

    protected static ?int $navigationSort = 5;

    protected static function fallbackPermissionResources(): array
    {
        return [];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.institution_programmes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.institution_programmes.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.institution_programmes.plural');
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
                TextColumn::make('institutions_count')->label(__('aho.fields.institutions_count'))->counts('institutions')->sortable(),
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
        // The table displays translated names and the number of linked training institutions.
        return parent::getEloquentQuery()
            ->with('translations')
            ->withCount('institutions');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstitutionProgrammes::route('/'),
            'create' => CreateInstitutionProgramme::route('/create'),
            'edit' => EditInstitutionProgramme::route('/{record}/edit'),
        ];
    }
}
