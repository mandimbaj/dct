<?php

namespace App\Support;

use App\Models\Country;
use App\Models\HealthServiceProgramme;
use App\Models\UhcClockIndicator;
use App\Models\UhcClockTheme;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Throwable;

class TranslatedReferenceForm
{
    /**
     * Build the common create/edit form for translated Django reference tables.
     *
     * @param  array<int, string>|null  $translationFields
     * @param  array<int, Component|Field>  $baseComponents
     */
    public static function configure(
        Schema $schema,
        string $modelClass,
        ?array $translationFields = null,
        array $baseComponents = [],
        bool $includeIdentityComponents = true,
    ): Schema {
        $primaryComponents = [
            ...($includeIdentityComponents ? self::identityComponents($modelClass) : []),
            ...$baseComponents,
        ];

        $components = [];

        if ($primaryComponents !== []) {
            $components[] = Section::make(__('aho.form_sections.primary_attributes'))
                ->schema($primaryComponents)
                ->columns(2);
        }

        $components[] = self::translationsSection($modelClass, $translationFields);

        return $schema->components($components);
    }

    /**
     * Build a relationship-backed multilingual editor for any warehouse model.
     *
     * Translation columns are read from the related Django translation table so fields such as
     * academic, theme, message or training institution contact details are not silently omitted.
     *
     * @param  array<int, string>|null  $translationFields
     */
    public static function translationsSection(string $modelClass, ?array $translationFields = null): Section
    {
        $columns = collect(self::translationColumns($modelClass))
            ->when(
                $translationFields !== null,
                fn ($columns) => $columns->whereIn('name', $translationFields),
            )
            ->values();

        return Section::make(__('aho.form_sections.translations'))
            ->description(__('aho.help.translations'))
            ->schema([
                Repeater::make('translations')
                    ->relationship(
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->orderByRaw("CASE language_code WHEN 'en' THEN 1 WHEN 'fr' THEN 2 WHEN 'pt' THEN 3 ELSE 4 END"),
                    )
                    ->hiddenLabel()
                    ->schema([
                        Select::make('language_code')
                            ->label(__('aho.fields.language'))
                            ->options(fn (): array => WarehouseLocale::supported())
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->distinct()
                            ->required(),
                        ...$columns
                            ->map(fn (array $column) => self::translationComponent($column))
                            ->all(),
                    ])
                    ->default([
                        ['language_code' => WarehouseLocale::current()],
                    ])
                    ->itemLabel(fn (array $state): string => WarehouseLocale::supported()[$state['language_code'] ?? ''] ?? __('aho.fields.language'))
                    ->addActionLabel(__('aho.actions.add_language'))
                    ->minItems(1)
                    ->maxItems(count(WarehouseLocale::supported()))
                    ->reorderable(false)
                    ->collapsible()
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    public static function locationCode(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('aho.form_sections.primary_attributes'))
                ->schema([
                    Select::make('location_id')
                        ->label(__('aho.fields.location'))
                        ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                            UserCountryAccess::scopeLocations(Country::query()),
                            keyName: 'location_id',
                        ))
                        ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                            UserCountryAccess::scopeLocations(Country::query()),
                            $search,
                            'location_id',
                        ))
                        ->searchable()
                        ->required(),
                    TextInput::make('country_code')
                        ->label(__('aho.fields.dial_code'))
                        ->required()
                        ->maxLength(15),
                ])
                ->columns(2),
        ]);
    }

    public static function customIcon(Schema $schema, string $modelClass): Schema
    {
        return self::configure($schema, $modelClass, baseComponents: [
            TextInput::make('unicode')
                ->label(__('aho.fields.icon_unicode'))
                ->required()
                ->maxLength(5),
            TextInput::make('version')
                ->label(__('aho.fields.version'))
                ->maxLength(15),
        ]);
    }

    public static function healthServiceProgramme(Schema $schema, string $modelClass): Schema
    {
        return self::configure($schema, $modelClass, baseComponents: [
            TextInput::make('level')
                ->label(__('aho.fields.level'))
                ->numeric()
                ->required(),
            Select::make('parent_id')
                ->label(__('aho.fields.parent'))
                ->options(fn (): array => SelectOptions::fromDisplayNameQuery(HealthServiceProgramme::query(), keyName: 'domain_id'))
                ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(HealthServiceProgramme::query(), $search, 'domain_id'))
                ->searchable(),
        ]);
    }

    public static function nationalObservatory(Schema $schema, string $modelClass): Schema
    {
        return self::configure(
            schema: $schema,
            modelClass: $modelClass,
            translationFields: ['name', 'shortname', 'custom_header', 'custom_footer', 'announcement', 'coat_arms', 'address'],
            baseComponents: [
                Select::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()),
                        keyName: 'location_id',
                    ))
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()),
                        $search,
                        'location_id',
                    ))
                    ->searchable()
                    ->required(),
                Select::make('user_id')
                    ->label(__('aho.fields.user'))
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->getSearchResultsUsing(fn (?string $search): array => User::query()
                        ->when($search, fn (Builder $query): Builder => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('email')->label(__('aho.fields.email'))->email()->maxLength(250),
                TextInput::make('phone_code')->label(__('aho.fields.phone_code'))->required()->maxLength(5),
                TextInput::make('phone_part')->label(__('aho.fields.phone_part'))->required()->maxLength(15),
                TextInput::make('phone_number')->label(__('aho.fields.phone_number'))->maxLength(20),
                TextInput::make('url')->label(__('aho.fields.url'))->url()->maxLength(2083),
            ],
        );
    }

    public static function uhcCountrySelection(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('aho.form_sections.primary_attributes'))
                ->schema([
                    Select::make('location_id')
                        ->label(__('aho.fields.location'))
                        ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                            UserCountryAccess::scopeLocations(Country::query()),
                            keyName: 'location_id',
                        ))
                        ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                            UserCountryAccess::scopeLocations(Country::query()),
                            $search,
                            'location_id',
                        ))
                        ->searchable()
                        ->required(),
                    Select::make('themes')
                        ->label(__('aho.fields.themes'))
                        ->relationship('themes', 'domain_id')
                        ->getOptionLabelFromRecordUsing(fn (UhcClockTheme $record): string => $record->display_name)
                        ->multiple()
                        ->preload()
                        ->searchable(),
                    Select::make('indicators')
                        ->label(__('aho.fields.indicators'))
                        ->relationship('indicators', 'id')
                        ->getOptionLabelFromRecordUsing(fn (UhcClockIndicator $record): string => trim(($record->indicator?->afrocode ? $record->indicator->afrocode.' - ' : '').($record->indicator?->display_name ?? $record->id)))
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])
                ->columns(2),
        ]);
    }

    /**
     * @return array<int, Field>
     */
    private static function identityComponents(string $modelClass): array
    {
        /** @var Model $model */
        $model = new $modelClass;
        $components = [];

        if (self::hasColumn($model, 'uuid')) {
            $components[] = Hidden::make('uuid')->default(fn (): string => (string) Str::uuid());
        }

        if (self::hasColumn($model, 'code')) {
            $components[] = TextInput::make('code')
                ->label(__('aho.fields.code'))
                ->default(fn (): string => GeneratedCode::forModel($model, 'code', null, self::columnLength($model, 'code') ?? 50))
                ->required(! self::isNullable($model, 'code'))
                ->maxLength(self::columnLength($model, 'code') ?? 50);
        }

        return $components;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function translationColumns(string $modelClass): array
    {
        try {
            /** @var Model $model */
            $model = new $modelClass;
            $translation = $model->translations()->getRelated();

            return collect(DatabaseSchema::connection($translation->getConnectionName())->getColumns($translation->getTable()))
                ->reject(fn (array $column): bool => in_array($column['name'], [
                    $translation->getKeyName(),
                    'language_code',
                    'master_id',
                    $translation->getCreatedAtColumn(),
                    $translation->getUpdatedAtColumn(),
                    'deleted_at',
                ], true))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private static function translationComponent(array $column)
    {
        $field = (string) $column['name'];
        $type = strtolower((string) ($column['type'] ?? ''));
        $typeName = strtolower((string) ($column['type_name'] ?? ''));
        $component = self::isLongText($field, $typeName)
            ? Textarea::make($field)->rows(4)
            : TextInput::make($field);

        $component->label(self::label($field));

        if ($component instanceof TextInput && self::isNumeric($typeName)) {
            $component->numeric();
        }

        if ($component instanceof TextInput && $field === 'email') {
            $component->email();
        }

        if ($component instanceof TextInput && in_array($field, ['url', 'external_url'], true)) {
            $component->url();
        }

        if ($component instanceof TextInput && $field === 'year_published') {
            $component
                ->minValue(1900)
                ->maxValue((int) date('Y') + 1);
        }

        if ($field === 'internal_url') {
            $component->helperText(__('aho.knowledge_products.help.internal_url'));
        }

        if (self::isRequired($column)) {
            $component->required();
        }

        $maxLength = self::length($type);

        if ($maxLength !== null && method_exists($component, 'maxLength')) {
            $component->maxLength($maxLength);
        }

        if ($component instanceof Textarea) {
            $component->columnSpanFull();
        }

        return $component;
    }

    private static function label(string $field): string
    {
        $keys = [
            'shortname' => 'aho.fields.short_name',
            'name' => 'aho.fields.name',
            'description' => 'aho.fields.description',
            'custom_header' => 'aho.fields.custom_header',
            'custom_footer' => 'aho.fields.custom_footer',
            'announcement' => 'aho.fields.announcement',
            'coat_arms' => 'aho.fields.coat_arms',
            'address' => 'aho.fields.address',
        ];
        $translationKey = $keys[$field] ?? 'aho.fields.'.$field;
        $translation = __($translationKey);

        return $translation === $translationKey
            ? Str::headline($field)
            : $translation;
    }

    private static function hasColumn(Model $model, string $column): bool
    {
        try {
            return DatabaseSchema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column);
        } catch (Throwable) {
            return false;
        }
    }

    private static function columnLength(Model $model, string $column): ?int
    {
        try {
            $column = collect(DatabaseSchema::connection($model->getConnectionName())->getColumns($model->getTable()))
                ->firstWhere('name', $column);
            $type = (string) ($column['type'] ?? '');

            return preg_match('/\((\d+)\)/', $type, $matches) === 1 ? (int) $matches[1] : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function isNullable(Model $model, string $columnName): bool
    {
        try {
            $column = collect(DatabaseSchema::connection($model->getConnectionName())->getColumns($model->getTable()))
                ->firstWhere('name', $columnName);

            return (bool) ($column['nullable'] ?? true);
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private static function isRequired(array $column): bool
    {
        return ! ($column['nullable'] ?? true)
            && ($column['default'] ?? null) === null
            && ! ($column['auto_increment'] ?? false);
    }

    private static function isNumeric(string $typeName): bool
    {
        return in_array($typeName, [
            'bigint',
            'decimal',
            'double',
            'float',
            'int',
            'integer',
            'mediumint',
            'real',
            'smallint',
            'tinyint',
        ], true);
    }

    private static function isLongText(string $field, string $typeName): bool
    {
        return str_contains($typeName, 'text')
            || in_array($field, [
                'abstract',
                'accreditation_info',
                'address',
                'announcement',
                'cordinate',
                'custom_footer',
                'custom_header',
                'definition',
                'denominator_description',
                'description',
                'message',
                'numerator_description',
                'posta',
                'preferred_datasources',
                'theme',
            ], true);
    }

    private static function length(string $type): ?int
    {
        return preg_match('/\((\d+)\)/', $type, $matches) === 1 ? (int) $matches[1] : null;
    }
}
