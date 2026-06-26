<?php

namespace App\Filament\Resources\DataIntegrationConnections;

use App\Filament\Clusters\DataIntegration;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\DataIntegrationConnections\Pages\ConfigureFieldMapping;
use App\Filament\Resources\DataIntegrationConnections\Pages\CreateDataIntegrationConnection;
use App\Filament\Resources\DataIntegrationConnections\Pages\EditDataIntegrationConnection;
use App\Filament\Resources\DataIntegrationConnections\Pages\ListDataIntegrationConnections;
use App\Filament\Resources\DataIntegrationConnections\Schemas\DataIntegrationConnectionForm;
use App\Filament\Resources\DataIntegrationConnections\Tables\DataIntegrationConnectionsTable;
use App\Models\DataIntegrationConnection;
use App\Support\UserCountryAccess;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Data-integration connection resource.
 *
 * Connection records define external providers; the custom mapping page stores how incoming
 * fields map to warehouse concepts.
 */
class DataIntegrationConnectionResource extends Resource
{
    protected static ?string $model = DataIntegrationConnection::class;

    protected static ?string $cluster = DataIntegration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sources';

    protected static ?string $slug = 'connections';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.data_integration.groups.sources');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.data_integration_connections.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.data_integration_connections.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.data_integration_connections.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return DataIntegrationConnectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataIntegrationConnectionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return UserCountryAccess::scope(parent::getEloquentQuery(), 'location_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataIntegrationConnections::route('/'),
            'create' => CreateDataIntegrationConnection::route('/create'),
            'edit' => EditDataIntegrationConnection::route('/{record}/edit'),
            'mapping' => ConfigureFieldMapping::route('/{record}/field-mapping'),
        ];
    }
}
