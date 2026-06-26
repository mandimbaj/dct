<?php

namespace App\Support;

use App\Models\FacilityServiceAvailability;
use App\Models\FacilityServiceCapacity;
use App\Models\FacilityServiceReadiness;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FacilityServiceRecordUniqueness
{
    /**
     * @return array<int, string>
     */
    public static function fieldsFor(string $modelClass): array
    {
        return match ($modelClass) {
            FacilityServiceAvailability::class => ['facility_id', 'domain_id', 'intervention_id', 'service_id', 'date_assessed'],
            FacilityServiceCapacity::class,
            FacilityServiceReadiness::class => ['facility_id', 'domain_id', 'units_id', 'date_assessed'],
            default => [],
        };
    }

    public static function supports(string $modelClass): bool
    {
        return self::fieldsFor($modelClass) !== [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function validateOrFail(string $modelClass, array $data, ?Model $ignoreRecord = null, string $errorStatePath = 'data.date_assessed'): void
    {
        if (! self::exists($modelClass, $data, $ignoreRecord)) {
            return;
        }

        throw ValidationException::withMessages([
            $errorStatePath => self::message(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function rule(string $modelClass, array $data, ?Model $ignoreRecord = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($data, $ignoreRecord, $modelClass): void {
            $data['date_assessed'] = $value;

            if (self::exists($modelClass, $data, $ignoreRecord)) {
                $fail(self::message());
            }
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function exists(string $modelClass, array $data, ?Model $ignoreRecord = null): bool
    {
        $fields = self::fieldsFor($modelClass);

        if ($fields === [] || self::hasMissingKey($data, $fields)) {
            return false;
        }

        /** @var Model $model */
        $model = new $modelClass;
        $query = $modelClass::query();

        foreach ($fields as $field) {
            $value = self::normalizeFieldValue($field, $data[$field]);

            $field === 'date_assessed'
                ? $query->whereDate($field, $value)
                : $query->where($field, $value);
        }

        if ($ignoreRecord instanceof $modelClass && $ignoreRecord->exists) {
            $query->where($model->getKeyName(), '!=', $ignoreRecord->getKey());
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    private static function hasMissingKey(array $data, array $fields): bool
    {
        foreach ($fields as $field) {
            if (blank($data[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeFieldValue(string $field, mixed $value): mixed
    {
        if ($field !== 'date_assessed') {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private static function message(): string
    {
        return __('aho.validation.facility_service_duplicate');
    }
}
