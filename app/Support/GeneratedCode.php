<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class GeneratedCode
{
    public static function ensureUuid(Model $model, string $column = 'uuid'): void
    {
        if (filled($model->{$column})) {
            return;
        }

        $model->{$column} = (string) Str::uuid();
    }

    public static function ensure(Model $model, string $column = 'code', ?string $prefix = null, int $maxLength = 50): void
    {
        if (filled($model->{$column})) {
            return;
        }

        $model->{$column} = self::forModel($model, $column, $prefix, $maxLength);
    }

    public static function forModel(Model $model, string $column = 'code', ?string $prefix = null, int $maxLength = 50): string
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $code = self::make($prefix ?? self::prefixFor($model), $maxLength);

            if (! self::exists($model, $column, $code)) {
                return $code;
            }
        }

        return self::make($prefix ?? self::prefixFor($model), $maxLength);
    }

    private static function make(string $prefix, int $maxLength): string
    {
        $prefix = (string) Str::of($prefix)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->substr(0, max(1, min(6, $maxLength)));

        $separator = $maxLength > (strlen($prefix) + 1) ? '-' : '';
        $randomLength = max(1, $maxLength - strlen($prefix) - strlen($separator));
        $random = strtoupper(Str::substr(str_replace('-', '', (string) Str::uuid()), 0, $randomLength));

        return Str::substr($prefix.$separator.$random, 0, $maxLength);
    }

    private static function prefixFor(Model $model): string
    {
        return (string) Str::of(class_basename($model))
            ->snake()
            ->explode('_')
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->implode('');
    }

    private static function exists(Model $model, string $column, string $code): bool
    {
        try {
            return $model->newQueryWithoutScopes()
                ->where($column, $code)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
