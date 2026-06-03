<?php

namespace App\Filament\Resources\NarrativeTypes;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\NarrativeTypes\Pages\EditNarrativeType;
use App\Filament\Resources\NarrativeTypes\Pages\CreateNarrativeType;
use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Indicators\IndicatorResource;
use App\Filament\Resources\NarrativeTypes\Pages\ListNarrativeTypes;
use App\Models\NarrativeType;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NarrativeTypeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = NarrativeType::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'narrative-types';

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.narrative_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.narrative_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.narrative_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'type_id', 'type')
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
        return [IndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNarrativeTypes::route('/'),
            'create' => CreateNarrativeType::route('/create'),
            'edit' => EditNarrativeType::route('/{record}/edit'),
        ];
    }
}
