<?php

namespace App\Filament\Resources\SpecialCategorizations;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\SpecialCategorizations\Pages\EditSpecialCategorization;
use App\Filament\Resources\SpecialCategorizations\Pages\CreateSpecialCategorization;
use App\Filament\Clusters\Regions;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\SpecialCategorizations\Pages\ListSpecialCategorizations;
use App\Models\SpecialCategorization;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SpecialCategorizationResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = SpecialCategorization::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'special-categorizations';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.special_categorizations.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.special_categorizations.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.special_categorizations.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'specialstates_id', 'special_status')
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

    protected static function fallbackPermissionResources(): array
    {
        return [CountryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialCategorizations::route('/'),
            'create' => CreateSpecialCategorization::route('/create'),
            'edit' => EditSpecialCategorization::route('/{record}/edit'),
        ];
    }
}
