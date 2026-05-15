<?php

namespace App\Filament\Resources\DqaCheckCategories;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaCheckCategories\Pages\ListDqaCheckCategories;
use App\Models\DataQuality\DqaInvalidCategoryOption;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DqaCheckCategoryResource extends Resource
{
    protected static ?string $model = DqaInvalidCategoryOption::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'check-categories';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_check_categories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_check_categories.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_check_categories.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::issueTable($table, static::getModel(), 'check_category_option');
    }

    public static function getEloquentQuery(): Builder
    {
        return DqaFilament::scopeIssueQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaCheckCategories::route('/'),
        ];
    }
}
