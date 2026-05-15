<?php

namespace App\Filament\Resources\IndicatorDomains;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\SearchesTranslatedRecords;
use App\Filament\Resources\IndicatorDomains\Pages\CreateIndicatorDomain;
use App\Filament\Resources\IndicatorDomains\Pages\EditIndicatorDomain;
use App\Filament\Resources\IndicatorDomains\Pages\ListIndicatorDomains;
use App\Filament\Resources\IndicatorDomains\Schemas\IndicatorDomainForm;
use App\Filament\Resources\IndicatorDomains\Tables\IndicatorDomainsTable;
use App\Models\IndicatorDomain;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndicatorDomainResource extends Resource
{
    use SearchesTranslatedRecords;

    protected static ?string $model = IndicatorDomain::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'domains';

    protected static ?string $navigationLabel = 'Indicator domains';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'indicator domain';

    protected static ?string $pluralModelLabel = 'indicator domains';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_domains.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.indicator_domains.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.indicator_domains.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return IndicatorDomainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndicatorDomainsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndicatorDomains::route('/'),
            'create' => CreateIndicatorDomain::route('/create'),
            'edit' => EditIndicatorDomain::route('/{record}/edit'),
        ];
    }
}
