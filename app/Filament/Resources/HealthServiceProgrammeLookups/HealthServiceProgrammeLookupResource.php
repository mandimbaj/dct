<?php

namespace App\Filament\Resources\HealthServiceProgrammeLookups;

use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthServiceProgrammeLookups\Pages\ListHealthServiceProgrammeLookups;
use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Models\HealthServiceProgrammeLookup;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HealthServiceProgrammeLookupResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = HealthServiceProgrammeLookup::class;

    protected static ?string $cluster = HealthServices::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'programmes-lookup';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_service_programme_lookup.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_service_programme_lookup.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_service_programme_lookup.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::simple(
            table: $table,
            columns: [
                'indicator_id' => 'indicator_id',
                'indicator_name' => 'indicator',
                'code' => 'code',
                'program_name' => 'programme',
                'level' => 'level',
            ],
            defaultSort: 'indicator_name',
            numericColumns: ['indicator_id', 'level'],
        );
    }

    protected static function fallbackPermissionResources(): array
    {
        return [HealthServiceValueResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthServiceProgrammeLookups::route('/'),
        ];
    }
}
