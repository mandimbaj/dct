<?php

namespace App\Filament\Resources\ValueDataTypes;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\TranslatedReferenceForm;
use App\Filament\Resources\ValueDataTypes\Pages\EditValueDataType;
use App\Filament\Resources\ValueDataTypes\Pages\CreateValueDataType;
use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\Indicators\IndicatorResource;
use App\Filament\Resources\ValueDataTypes\Pages\ListValueDataTypes;
use App\Models\ValueDataType;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ValueDataTypeResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = ValueDataType::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'value-types';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.value_data_types.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.value_data_types.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.value_data_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::translatedReference($table, 'valuetype_id', 'value_type')
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
        return [IndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListValueDataTypes::route('/'),
            'create' => CreateValueDataType::route('/create'),
            'edit' => EditValueDataType::route('/{record}/edit'),
        ];
    }
}
