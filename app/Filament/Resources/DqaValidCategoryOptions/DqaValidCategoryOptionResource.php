<?php

namespace App\Filament\Resources\DqaValidCategoryOptions;

use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DqaValidCategoryOptions\Pages\ListDqaValidCategoryOptions;
use App\Models\DataQuality\DqaValidCategoryOption;
use App\Support\DataQuality\DqaFilament;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DqaValidCategoryOptionResource extends Resource
{
    protected static ?string $model = DqaValidCategoryOption::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'categoryoptions';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_valid_category_options.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_valid_category_options.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_valid_category_options.plural');
    }

    public static function table(Table $table): Table
    {
        return DqaFilament::lookupTable($table, static::getModel(), [
            'id' => 'id',
            'afrocode' => 'afro_code',
            'indicator_id' => 'indicator_id',
            'categoryoption_id' => 'category_option',
            'categoryoptionid' => 'category_option_id',
            'user_id' => 'user',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaValidCategoryOptions::route('/'),
        ];
    }
}
