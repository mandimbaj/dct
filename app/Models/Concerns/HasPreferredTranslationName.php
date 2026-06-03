<?php

namespace App\Models\Concerns;

use App\Support\TextEncoding;
use App\Support\WarehouseLocale;

trait HasPreferredTranslationName
{
    /**
     * Resolve the translated name in the active warehouse language, then fall back safely.
     */
    protected function preferredTranslationName(?string $fallback = null): string
    {
        return $this->preferredTranslationValue('name', $fallback) ?? (string) ($fallback ?? $this->getKey());
    }

    /**
     * Read any translated field using the same language priority as display names.
     *
     * This is used by Health workforce models for shortname, theme and message fields.
     */
    protected function preferredTranslationValue(string $field, ?string $fallback = null): ?string
    {
        $languages = WarehouseLocale::preferredLanguages();

        if ($this->relationLoaded('translations')) {
            $translation = $this->translations
                ->whereIn('language_code', $languages)
                ->filter(fn ($translation): bool => filled($translation->{$field}))
                ->sortBy(fn ($translation): int => array_search($translation->language_code, $languages, true))
                ->first();

            if (filled($translation?->{$field})) {
                return TextEncoding::clean($translation->{$field}) ?? $translation->{$field};
            }
        }

        $order = collect($languages)
            ->map(fn (string $language): string => "'".str_replace("'", "''", $language)."'")
            ->implode(',');

        $name = $this->translations()
            ->whereIn('language_code', $languages)
            ->whereNotNull($field)
            ->orderByRaw("FIELD(language_code, {$order})")
            ->value($field);

        return TextEncoding::clean($name) ?? $fallback;
    }
}
