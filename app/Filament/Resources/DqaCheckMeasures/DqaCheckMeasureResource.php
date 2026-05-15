<?php

namespace App\Filament\Resources\DqaCheckMeasures;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaCheckMeasures\Pages\ListDqaCheckMeasures;
use App\Models\DataQuality\DqaInvalidMeasureType;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaCheckMeasureResource extends Resource
{
    protected static ?string $model = DqaInvalidMeasureType::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'check-measures';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_check_measures.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_check_measures.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_check_measures.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'check_mesure_type');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaCheckMeasures::route('/'),
        ];
    }
}
