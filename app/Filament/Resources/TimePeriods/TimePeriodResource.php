<?php

namespace App\Filament\Resources\TimePeriods;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\TimePeriods\Pages\CreateTimePeriod;
use App\Filament\Resources\TimePeriods\Pages\EditTimePeriod;
use App\Filament\Resources\TimePeriods\Pages\ListTimePeriods;
use App\Filament\Resources\TimePeriods\Schemas\TimePeriodForm;
use App\Filament\Resources\TimePeriods\Tables\TimePeriodsTable;
use App\Models\TimePeriod;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TimePeriodResource extends Resource
{
    protected static ?string $model = TimePeriod::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'periods';

    protected static ?string $navigationLabel = 'Periods';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'period';

    protected static ?string $pluralModelLabel = 'periods';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.periods.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.periods.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.periods.plural');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'name',
            'shortname',
            'description',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return TimePeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimePeriodsTable::configure($table);
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
            'index' => ListTimePeriods::route('/'),
            'create' => CreateTimePeriod::route('/create'),
            'edit' => EditTimePeriod::route('/{record}/edit'),
        ];
    }
}
