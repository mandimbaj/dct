<?php

namespace App\Filament\Resources\DqaMissingValues;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaMissingValues\Pages\ListDqaMissingValues;
use App\Models\DataQuality\DqaMissingValue;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaMissingValueResource extends Resource
{
    protected static ?string $model = DqaMissingValue::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'missing-values';

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_missing_values.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_missing_values.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_missing_values.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'remarks', true);
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaMissingValues::route('/'),
        ];
    }
}
