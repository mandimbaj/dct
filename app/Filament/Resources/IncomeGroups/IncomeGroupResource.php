<?php

namespace App\Filament\Resources\IncomeGroups;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\IncomeGroups\Pages\EditIncomeGroup;
use App\Filament\Resources\IncomeGroups\Pages\CreateIncomeGroup;
use App\Filament\Clusters\Regions;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\IncomeGroups\Pages\ListIncomeGroups;
use App\Models\IncomeGroup;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IncomeGroupResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = IncomeGroup::class;

    protected static ?string $cluster = Regions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'income-groups';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.income_groups.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.income_groups.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.income_groups.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'wb_income_groupid', 'income_group')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function fallbackPermissionResources(): array
    {
        return [CountryResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomeGroups::route('/'),
            'create' => CreateIncomeGroup::route('/create'),
            'edit' => EditIncomeGroup::route('/{record}/edit'),
        ];
    }
}
