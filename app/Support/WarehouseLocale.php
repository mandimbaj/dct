<?php

namespace App\Support;

class WarehouseLocale
{
    /**
     * @return array<string, string>
     */
    public static function supported(): array
    {
        return [
            'en' => 'English',
            'fr' => 'Français',
            'pt' => 'Português',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(static::supported());
    }

    public static function normalize(?string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', (string) $locale));
        $locale = substr($locale, 0, 2);

        return in_array($locale, static::codes(), true) ? $locale : 'en';
    }

    public static function current(): string
    {
        return static::normalize(app()->getLocale());
    }

    /**
     * @return array<int, string>
     */
    public static function preferredLanguages(): array
    {
        return array_values(array_unique([
            static::current(),
            'fr',
            'pt',
            'en',
        ]));
    }

    public static function set(?string $locale): string
    {
        $locale = static::normalize($locale);

        app()->setLocale($locale);

        if (app()->bound('session.store')) {
            session(['locale' => $locale]);
        }

        return $locale;
    }
}
