<?php

namespace App\Filament\Resources\DqaCheckPeriods;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaCheckPeriods\Pages\ListDqaCheckPeriods;
use App\Models\DataQuality\DqaInvalidPeriod;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaCheckPeriodResource extends Resource
{
    protected static ?string $model = DqaInvalidPeriod::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'check-periods';

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_check_periods.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_check_periods.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_check_periods.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'check_year');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaCheckPeriods::route('/'),
        ];
    }
}
