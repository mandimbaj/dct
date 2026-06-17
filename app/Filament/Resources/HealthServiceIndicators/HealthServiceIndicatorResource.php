<?php

namespace App\Filament\Resources\HealthServiceIndicators;

use App\Filament\Resources\HealthServiceIndicators\Pages\EditHealthServiceIndicator;
use App\Filament\Resources\HealthServiceIndicators\Pages\CreateHealthServiceIndicator;
use App\Filament\Clusters\HealthServices;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\HealthServiceValues\HealthServiceValueResource;
use App\Filament\Resources\HealthServiceIndicators\Pages\ListHealthServiceIndicators;
use App\Models\Indicator;
use App\Models\IndicatorReference;
use App\Support\FilamentSearch;
use App\Support\SelectOptions;
use App\Support\UserPermissions;
use App\Support\WarehouseLocale;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        return $schema->components([
            Hidden::make('uuid'),
            Hidden::make('afrocode'),
            Hidden::make('gen_code'),
            Hidden::make('translation_language_code')
                ->default(fn (): string => WarehouseLocale::current()),

            Section::make(__('aho.form_sections.primary_attributes'))
                ->schema([
                    TextInput::make('translation_name')
                        ->label(__('aho.fields.name'))
                        ->required()
                        ->maxLength(200),
                    TextInput::make('translation_shortname')
                        ->label(__('aho.fields.short_name'))
                        ->maxLength(200),
                    Textarea::make('translation_definition')
                        ->label(__('aho.fields.definition'))
                        ->rows(4)
                        ->columnSpanFull(),
                    Select::make('reference_id')
                        ->label(__('aho.fields.reference'))
                        ->options(fn (): array => SelectOptions::fromDisplayNameQuery(static::hscReferenceQuery(), keyName: 'reference_id'))
                        ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(static::hscReferenceQuery(), $search, 'reference_id'))
                        ->default(fn (): ?int => static::hscReferenceId())
                        ->searchable()
                        ->required(),
                ])
                ->columns(2),

            Section::make(__('aho.form_sections.secondary_attributes'))
                ->schema([
                    Textarea::make('translation_numerator_description')
                        ->label(__('aho.fields.numerator_description'))
                        ->rows(4),
                    Textarea::make('translation_denominator_description')
                        ->label(__('aho.fields.denominator_description'))
                        ->rows(4),
                    Textarea::make('translation_preferred_datasources')
                        ->label(__('aho.fields.preferred_datasources'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function canCreate(): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_CREATE);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_UPDATE);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_DELETE);
    }

    public static function canDeleteAny(): bool
    {
        return static::canUseInheritedPermission(UserPermissions::ACTION_DELETE);
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
            ->whereHas('reference', fn (Builder $query): Builder => static::applyHscReferenceConstraint($query));
    }

    public static function hscReferenceId(): ?int
    {
        return static::hscReferenceQuery()->value('reference_id') ?? 5;
    }

    public static function hscReferenceQuery(): Builder
    {
        return static::applyHscReferenceConstraint(IndicatorReference::query());
    }

    private static function applyHscReferenceConstraint(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('code', 'GIR0005')->orWhere('reference_id', 5);
        });
    }

    protected static function fallbackPermissionResources(): array
    {
        return [HealthServiceValueResource::class];
    }

    private static function canUseInheritedPermission(string $action): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        foreach ([static::class, ...static::fallbackPermissionResources()] as $resourceClass) {
            if (UserPermissions::allowsResource($user, $resourceClass, $action)) {
                return true;
            }
        }

        return false;
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
