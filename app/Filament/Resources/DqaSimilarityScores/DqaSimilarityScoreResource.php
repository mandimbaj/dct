<?php

namespace App\Filament\Resources\DqaSimilarityScores;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Support\WarehouseForm;
use App\Filament\Resources\DqaSimilarityScores\Pages\EditDqaSimilarityScore;
use App\Filament\Resources\DqaSimilarityScores\Pages\CreateDqaSimilarityScore;
use App\Filament\Clusters\DataQuality;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\DqaMultipleMeasures\DqaMultipleMeasureResource;
use App\Filament\Resources\DqaSimilarityScores\Pages\ListDqaSimilarityScores;
use App\Models\DataQuality\DqaSimilarityScore;
use App\Support\FilamentReadOnlyTables;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DqaSimilarityScoreResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = DqaSimilarityScore::class;

    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'similarity-scores';

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.dqa_similarity_scores.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.dqa_similarity_scores.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.dqa_similarity_scores.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return FilamentReadOnlyTables::simple(
            table: $table,
            columns: [
                'id' => 'id',
                'source_indicator' => 'source_indicator',
                'similar_indicator' => 'similar_indicator',
                'score' => 'score',
                'user_id' => 'user',
            ],
            defaultSort: 'id',
            numericColumns: ['id', 'score', 'user_id'],
            direction: 'desc',
        )
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
        return [DqaMultipleMeasureResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDqaSimilarityScores::route('/'),
            'create' => CreateDqaSimilarityScore::route('/create'),
            'edit' => EditDqaSimilarityScore::route('/{record}/edit'),
        ];
    }
}
