<?php

namespace App\Filament\Resources\DqaValidMeasureTypes;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaValidMeasureTypes\Pages\ListDqaValidMeasureTypes;
use App\Models\DataQuality\DqaValidMeasureType;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DqaValidMeasureTypeResource extends Resource
{
    protected static ?string $model = DqaValidMeasureType::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'measuretypes';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_valid_measure_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_valid_measure_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_valid_measure_types.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::lookupTable($table, static::getModel(), [
            'id' => 'id',
            'afrocode' => 'afro_code',
            'indicator_id' => 'indicator_id',
            'measure_type_id' => 'measure_type',
            'measuremethod_id' => 'measure_method',
            'user_id' => 'user',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaValidMeasureTypes::route('/'),
        ];
    }
}
