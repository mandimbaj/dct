<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class ResourceTranslations
{
    /**
     * @param array<int, string> $fields
     * @return array<string, mixed>
     */
    public static function fill(array $data, Model $record, array $fields): array
    {
        $language = WarehouseLocale::current();
        $languages = WarehouseLocale::preferredLanguages();

        $translation = $record->translations()
            ->whereIn('language_code', $languages)
            ->get()
            ->sortBy(fn ($translation): int => array_search($translation->language_code, $languages, true))
            ->first();

        $data['translation_language_code'] = $translation?->language_code ?? $language;

        foreach ($fields as $field) {
            $data['translation_'.$field] = $translation?->{$field};
        }

        return $data;
    }

    /**
     * @param array<int, string> $fields
     * @return array<string, mixed>
     */
    public static function extract(array &$data, array $fields): array
    {
        $payload = [
            'language_code' => WarehouseLocale::normalize($data['translation_language_code'] ?? WarehouseLocale::current()),
        ];

        unset($data['translation_language_code']);

        foreach ($fields as $field) {
            $key = 'translation_'.$field;

            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $payload[$field] = is_string($value) && trim($value) === '' ? null : $value;

            unset($data[$key]);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function sync(Model $record, array $payload): void
    {
        if (blank($payload['name'] ?? null) && blank($payload['title'] ?? null)) {
            return;
        }

        $language = WarehouseLocale::normalize($payload['language_code'] ?? WarehouseLocale::current());
        unset($payload['language_code']);

        $record->translations()->updateOrCreate(
            ['language_code' => $language],
            ['language_code' => $language, ...$payload],
        );
    }
}
