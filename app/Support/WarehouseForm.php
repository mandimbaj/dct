<?php

namespace App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Throwable;

class WarehouseForm
{
    public static function configure(Schema $schema, string $modelClass): Schema
    {
        /** @var Model $model */
        $model = new $modelClass;

        try {
            $columns = DatabaseSchema::connection($model->getConnectionName())->getColumns($model->getTable());
        } catch (Throwable) {
            return $schema->components([
                Placeholder::make('database_unavailable')
                    ->label('Database')
                    ->content('The warehouse table could not be inspected. Check the MySQL connection.'),
            ]);
        }

        $components = collect($columns)
            ->map(fn (array $column) => self::componentFor($column, $model))
            ->values()
            ->all();

        if ($components === []) {
            $components[] = Placeholder::make('no_editable_fields')
                ->label('Fields')
                ->content('No editable columns were detected for this table.');
        }

        return $schema->components($components);
    }

    private static function componentFor(array $column, Model $model)
    {
        $name = $column['name'];
        $type = strtolower((string) ($column['type'] ?? ''));
        $typeName = strtolower((string) ($column['type_name'] ?? ''));
        $label = self::label($name);
        $readOnly = self::isReadOnlyColumn($column, $model);

        $component = match (true) {
            self::hasRelationForField($name, $model) => self::selectForForeignKey($name, $model),
            self::isBoolean($type, $typeName) => Toggle::make($name),
            self::isDateTime($typeName) => DateTimePicker::make($name)->seconds(false),
            self::isDate($typeName) => DatePicker::make($name),
            self::isLongText($name, $typeName) => Textarea::make($name)->rows(4),
            default => TextInput::make($name),
        };

        $component->label($label);

        if ($component instanceof TextInput && self::isNumeric($typeName)) {
            $component->numeric();
        }

        if ($readOnly) {
            $component->disabled()->dehydrated(false);
        }

        if (($length = self::length($type)) !== null && method_exists($component, 'maxLength')) {
            $component->maxLength($length);
        }

        if (! $readOnly && $name === 'uuid' && method_exists($component, 'default')) {
            $component->default(fn (): string => (string) Str::uuid());
        }

        if (! $readOnly && $name !== 'uuid' && ($column['default'] ?? null) !== null && method_exists($component, 'default')) {
            $component->default($column['default']);
        }

        if (! $readOnly && self::isRequired($column)) {
            $component->required();
        }

        if ($component instanceof Textarea || $name === 'uuid') {
            $component->columnSpanFull();
        }

        return $component;
    }

    private static function isReadOnlyColumn(array $column, Model $model): bool
    {
        $name = $column['name'];
        $timestamps = array_filter([
            $model->getCreatedAtColumn(),
            $model->getUpdatedAtColumn(),
            'deleted_at',
        ]);

        return $name === $model->getKeyName()
            || in_array($name, $timestamps, true)
            || ($column['auto_increment'] ?? false);
    }

    private static function hasRelationForField(string $fieldName, Model $model): bool
    {
        $relationName = self::relationNameForField($fieldName, $model);

        if (is_string($relationName) && method_exists($model, $relationName)) {
            return true;
        }

        return self::relationNameByForeignKey($fieldName, $model) !== null;
    }

    private static function selectForForeignKey(string $fieldName, Model $model): Select
    {
        $relationName = self::relationNameForField($fieldName, $model)
            ?? self::relationNameByForeignKey($fieldName, $model);

        if ($relationName !== null && method_exists($model, $relationName)) {
            try {
                $relation = $model->$relationName();
            } catch (Throwable) {
                $relation = null;
            }

            if ($relation instanceof Relation) {
                $related = $relation->getRelated();
                $label = self::selectLabelForModel($related);

                return Select::make($fieldName)
                    ->options(self::optionsForRelatedModel($related, $relation->getOwnerKeyName() ?? $fieldName, $label))
                    ->searchable();
            }
        }

        return Select::make($fieldName)->searchable();
    }

    private static function relationNameForField(string $fieldName, Model $model): ?string
    {
        $candidates = [
            Str::camel(str_replace(['_id', '_key'], '', $fieldName)),
            Str::camel(Str::singular(str_replace(['_id', '_key'], '', $fieldName))),
            Str::camel($fieldName),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && method_exists($model, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function relationNameByForeignKey(string $fieldName, Model $model): ?string
    {
        foreach (get_class_methods($model) as $method) {
            if ($method === '__construct') {
                continue;
            }

            if (! method_exists($model, $method)) {
                continue;
            }

            try {
                $relation = $model->$method();
            } catch (Throwable) {
                continue;
            }

            if (! $relation instanceof Relation) {
                continue;
            }

            if ($relation->getForeignKeyName() === $fieldName) {
                return $method;
            }
        }

        return null;
    }

    private static function selectLabelForModel(Model $model): string
    {
        $candidates = [
            'display_name',
            'name',
            'title',
            'label',
            'translation_name',
            'code',
            'shortname',
            'iso_alpha',
            'description',
        ];

        foreach ($candidates as $candidate) {
            if (self::modelHasColumn($model, $candidate) || self::modelHasAccessor($model, $candidate)) {
                return $candidate;
            }
        }

        return $model->getKeyName();
    }

    private static function optionsForRelatedModel(Model $model, string $keyName, string $label): array
    {
        try {
            $query = $model::query();

            if (self::modelHasColumn($model, $label)) {
                $query->orderBy($label);
            } else {
                $query->orderBy($keyName);
            }

            return $query->get()
                ->mapWithKeys(fn (Model $record) => [ $record->$keyName => (string) ($record->$label ?? $record->$keyName) ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private static function modelHasColumn(Model $model, string $column): bool
    {
        try {
            return in_array($column, DatabaseSchema::connection($model->getConnectionName())->getColumnListing($model->getTable()), true);
        } catch (Throwable) {
            return false;
        }
    }

    private static function modelHasAccessor(Model $model, string $attribute): bool
    {
        return method_exists($model, 'get'.Str::studly($attribute).'Attribute');
    }

    private static function isRequired(array $column): bool
    {
        return ! ($column['nullable'] ?? true)
            && ($column['default'] ?? null) === null
            && ! ($column['auto_increment'] ?? false);
    }

    private static function label(string $column): string
    {
        $translationKey = 'aho.fields.'.$column;
        $translation = __($translationKey);

        return $translation === $translationKey
            ? Str::headline(str_replace(['_id', '_key'], '', $column))
            : $translation;
    }

    private static function isBoolean(string $type, string $typeName): bool
    {
        return in_array($typeName, ['boolean', 'bool'], true)
            || str_contains($type, 'tinyint(1)');
    }

    private static function isDateTime(string $typeName): bool
    {
        return in_array($typeName, ['datetime', 'timestamp'], true);
    }

    private static function isDate(string $typeName): bool
    {
        return $typeName === 'date';
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

    private static function isLongText(string $name, string $typeName): bool
    {
        return str_contains($typeName, 'text')
            || in_array($typeName, ['json', 'longtext', 'mediumtext'], true)
            || Str::contains($name, ['abstract', 'comment', 'description', 'note']);
    }

    private static function length(string $type): ?int
    {
        if (preg_match('/\((\d+)\)/', $type, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
