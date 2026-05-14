<?php

namespace App\Models\Concerns;

use App\Support\TextEncoding;
use App\Support\WarehouseLocale;

trait HasPreferredTranslationName
{
    protected function preferredTranslationName(?string $fallback = null): string
    {
        $languages = WarehouseLocale::preferredLanguages();

        if ($this->relationLoaded('translations')) {
            $translation = $this->translations
                ->whereIn('language_code', $languages)
                ->filter(fn ($translation): bool => filled($translation->name))
                ->sortBy(fn ($translation): int => array_search($translation->language_code, $languages, true))
                ->first();

            if (filled($translation?->name)) {
                return TextEncoding::clean($translation->name) ?? $translation->name;
            }
        }

        $order = collect($languages)
            ->map(fn (string $language): string => "'".str_replace("'", "''", $language)."'")
            ->implode(',');

        $name = $this->translations()
            ->whereIn('language_code', $languages)
            ->whereNotNull('name')
            ->orderByRaw("FIELD(language_code, {$order})")
            ->value('name');

        return TextEncoding::clean($name) ?? (string) ($fallback ?? $this->getKey());
    }
}
