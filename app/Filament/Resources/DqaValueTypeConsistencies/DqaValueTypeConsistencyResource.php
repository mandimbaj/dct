<?php

namespace App\Filament\Resources\DqaValueTypeConsistencies;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaValueTypeConsistencies\Pages\ListDqaValueTypeConsistencies;
use App\Models\DataQuality\DqaValueTypeConsistency;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaValueTypeConsistencyResource extends Resource
{
    protected static ?string $model = DqaValueTypeConsistency::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'value-type-consistencies';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_value_type_consistencies.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_value_type_consistencies.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_value_type_consistencies.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'check_value');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaValueTypeConsistencies::route('/'),
        ];
    }
}
