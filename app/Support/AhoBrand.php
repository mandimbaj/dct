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

    /**
     * @return array<string, string>
     */
    public static function whiteLogos(): array
    {
        return [
            'en' => 'images/aho-logo-combined-en-white.png',
            'fr' => 'images/aho-logo-combined-fr-white.png',
            'pt' => 'images/aho-logo-combined-pt-white.png',
        ];
    }

    public static function whiteLogoPath(?string $locale = null): string
    {
        $locale = WarehouseLocale::normalize($locale ?? app()->getLocale());

        return static::whiteLogos()[$locale] ?? static::whiteLogos()['en'];
    }

    public static function appName(): string
    {
        return __('aho.brand.app_name');
    }
}
