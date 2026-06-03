<?php

namespace App\Filament\Resources\HealthServiceIndicators;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Indicators\Schemas\IndicatorForm;
use App\Filament\Resources\HealthServiceIndicators\Pages\EditHealthServiceIndicator;
use App\Filament\Resources\HealthServiceIndicators\Pages\CreateHealthServiceIndicator;
use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Filament\Resources\HealthServiceIndicators\Pages\ListHealthServiceIndicators;
use App\Models\Indicator;
use App\Support\FilamentSearch;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HealthServiceIndicatorResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = Indicator::class;

    protected static ?string $cluster = HealthServices::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'hsc-indicators';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.health_service_indicators.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.health_service_indicators.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.health_service_indicators.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return IndicatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('afrocode')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['afrocode', 'gen_code'],
                    relations: [
                        'translations' => ['name', 'shortname', 'definition'],
                        'reference.translations' => ['name'],
                    ],
                    numericColumns: ['indicator_id'],
                );
            })
            ->columns([
                TextColumn::make('indicator_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('afrocode')->label(__('aho.fields.code'))->sortable(),
                TextColumn::make('display_name')->label(__('aho.fields.indicator'))->wrap(),
                TextColumn::make('reference.display_name')->label(__('aho.fields.reference'))->wrap()->toggleable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['translations', 'reference.translations'])
            ->whereHas('reference', function (Builder $query): Builder {
                return $query->where('code', 'GIR0005')->orWhere('reference_id', 5);
            });
    }

    protected static function fallbackPermissionResources(): array
    {
        return [HealthServiceValueResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthServiceIndicators::route('/'),
            'create' => CreateHealthServiceIndicator::route('/create'),
            'edit' => EditHealthServiceIndicator::route('/{record}/edit'),
        ];
    }
}
