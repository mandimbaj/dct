<?php

namespace App\Support;

class AhoBrand
{
    /**
     * @return array<string, string>
     */
    public static function logos(): array
    {
        return [
            'en' => 'images/aho-logo-combined-en.png',
            'fr' => 'images/aho-logo-combined-fr.png',
            'pt' => 'images/aho-logo-combined-pt.png',
        ];
    }

    public static function logoPath(?string $locale = null): string
    {
        $locale = WarehouseLocale::normalize($locale ?? app()->getLocale());

        return static::logos()[$locale] ?? static::logos()['en'];
    }

    public static function appName(): string
    {
        return __('aho.brand.app_name');
    }
}
