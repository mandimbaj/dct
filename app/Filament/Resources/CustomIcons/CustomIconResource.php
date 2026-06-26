<?php

namespace App\Filament\Resources\CustomIcons;

use App\Filament\Clusters\Indicators;
use App\Filament\Resources\AhoResource as Resource;
use App\Filament\Resources\Concerns\UsesFallbackResourcePermission;
use App\Filament\Resources\CustomIcons\Pages\CreateCustomIcon;
use App\Filament\Resources\CustomIcons\Pages\EditCustomIcon;
use App\Filament\Resources\CustomIcons\Pages\ListCustomIcons;
use App\Filament\Resources\Indicators\IndicatorResource;
use App\Models\CustomIcon;
use App\Support\FilamentSearch;
use App\Support\TranslatedReferenceForm;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class CustomIconResource extends Resource
{
    use UsesFallbackResourcePermission;

    protected static ?string $model = CustomIcon::class;

    protected static ?string $cluster = Indicators::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'References';

    protected static ?string $slug = 'custom-icons';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('aho.navigation.references');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.custom_icons.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('aho.resources.custom_icons.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('aho.resources.custom_icons.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return TranslatedReferenceForm::customIcon($schema, static::getModel());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('icon_id')
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['code', 'unicode', 'version'],
                    relations: ['translations' => ['name', 'shortname', 'description']],
                    numericColumns: ['icon_id'],
                );
            })
            ->columns([
                TextColumn::make('icon_id')->label(__('aho.fields.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('preview')
                    ->label(__('aho.fields.preview'))
                    ->state(fn (CustomIcon $record): ?string => $record->code)
                    ->formatStateUsing(function (?string $state, CustomIcon $record): HtmlString {
                        $classes = trim((string) preg_replace('/[^a-zA-Z0-9\-\s]/', '', (string) $state));
                        $title = e($record->display_name);
                        $unicode = e($record->unicode);

                        if ($classes === '') {
                            return new HtmlString('<span class="aho-fa-preview aho-fa-preview--fallback">'.$unicode.'</span>');
                        }

                        return new HtmlString(
                            '<span class="aho-fa-preview" title="'.$title.'">'.
                                '<i class="'.$classes.'" aria-hidden="true"></i>'.
                                '<span class="sr-only">'.$title.'</span>'.
                            '</span>'
                        );
                    })
                    ->html()
                    ->toggleable(),
                TextColumn::make('display_name')->label(__('aho.fields.name'))->wrap(),
                TextColumn::make('unicode')->label(__('aho.fields.icon_unicode'))->sortable(),
                TextColumn::make('code')->label(__('aho.fields.code'))->sortable()->toggleable(),
                TextColumn::make('version')->label(__('aho.fields.version'))->sortable()->toggleable(),
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
        return parent::getEloquentQuery()->with('translations');
    }

    protected static function fallbackPermissionResources(): array
    {
        return [IndicatorResource::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomIcons::route('/'),
            'create' => CreateCustomIcon::route('/create'),
            'edit' => EditCustomIcon::route('/{record}/edit'),
        ];
    }
}
