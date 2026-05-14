<?php

namespace App\Filament\Resources\MeasureMethods;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\MeasureMethods\Pages\CreateMeasureMethod;
use App\Filament\Resources\MeasureMethods\Pages\EditMeasureMethod;
use App\Filament\Resources\MeasureMethods\Pages\ListMeasureMethods;
use App\Filament\Resources\MeasureMethods\Schemas\MeasureMethodForm;
use App\Filament\Resources\MeasureMethods\Tables\MeasureMethodsTable;
use App\Models\MeasureMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MeasureMethodResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = MeasureMethod::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'measure-methods';

    protected static ?string $navigationLabel = 'Measure methods';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'measure method';

    protected static ?string $pluralModelLabel = 'measure methods';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.measure_methods.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.measure_methods.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.measure_methods.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return MeasureMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeasureMethodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasureMethods::route('/'),
            'create' => CreateMeasureMethod::route('/create'),
            'edit' => EditMeasureMethod::route('/{record}/edit'),
        ];
    }
}
