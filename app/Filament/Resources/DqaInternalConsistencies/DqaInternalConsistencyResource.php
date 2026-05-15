<?php

namespace App\Filament\Resources\DqaInternalConsistencies;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaInternalConsistencies\Pages\ListDqaInternalConsistencies;
use App\Models\DataQuality\DqaInternalConsistency;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaInternalConsistencyResource extends Resource
{
    protected static ?string $model = DqaInternalConsistency::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'internal-consistencies';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_internal_consistencies.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_internal_consistencies.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_internal_consistencies.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'internal_consistency');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaInternalConsistencies::route('/'),
        ];
    }
}
