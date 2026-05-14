<?php

namespace App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
            ->reject(fn (array $column): bool => self::shouldSkipColumn($column, $model))
            ->map(fn (array $column) => self::componentFor($column))
            ->values()
            ->all();

        if ($components === []) {
            $components[] = Placeholder::make('no_editable_fields')
                ->label('Fields')
                ->content('No editable columns were detected for this table.');
        }

        return $schema->components($components);
    }

    private static function shouldSkipColumn(array $column, Model $model): bool
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

    private static function componentFor(array $column)
    {
        $name = $column['name'];
        $type = strtolower((string) ($column['type'] ?? ''));
        $typeName = strtolower((string) ($column['type_name'] ?? ''));
        $label = self::label($name);

        $component = match (true) {
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

        if (($length = self::length($type)) !== null && method_exists($component, 'maxLength')) {
            $component->maxLength($length);
        }

        if ($name === 'uuid' && method_exists($component, 'default')) {
            $component->default(fn (): string => (string) Str::uuid());
        }

        if (self::isRequired($column)) {
            $component->required();
        }

        if ($component instanceof Textarea || $name === 'uuid') {
            $component->columnSpanFull();
        }

        return $component;
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
