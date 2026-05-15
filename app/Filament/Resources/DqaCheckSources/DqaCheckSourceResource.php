<?php

namespace App\Filament\Resources\DqaCheckSources;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaCheckSources\Pages\ListDqaCheckSources;
use App\Models\DataQuality\DqaInvalidDataSource;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaCheckSourceResource extends Resource
{
    protected static ?string $model = DqaInvalidDataSource::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'check-sources';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_check_sources.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_check_sources.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_check_sources.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'check_data_source');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaCheckSources::route('/'),
        ];
    }
}
