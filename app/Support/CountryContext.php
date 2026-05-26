<?php

namespace App\Support;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CountryContext
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user->canViewAllCountries()) {
            return self::africaPayload();
        }

        if (blank($user->location_id)) {
            return null;
        }

        $country = $user->relationLoaded('location')
            ? $user->location
            : $user->location()->with('translations')->first();

        if (! $country instanceof Country) {
            return null;
        }

        if ((int) $country->locationlevel_id !== 2) {
            return null;
        }

        return Cache::remember(
            'country-context-card.'.$country->getKey().'.'.WarehouseLocale::current(),
            now()->addHours(12),
            fn (): array => self::payload($country),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function payload(Country $country): array
    {
        $iso = self::iso2($country->iso_alpha);
        $isoLower = strtolower($iso);

        return [
            'name' => $country->display_name,
            'iso' => strtoupper($iso),
            'svg_html' => CountryMapData::buildSvgHtml($iso),
            'flag_url' => 'https://flagcdn.com/w80/'.$isoLower.'.png',
            'flag_srcset' => 'https://flagcdn.com/w160/'.$isoLower.'.png 2x',
            'map_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function africaPayload(): array
    {
        return [
            'name' => __('aho.country_context.africa_region'),
            'iso' => 'AFRO',
            'svg_html' => '<img src="'.asset('images/africa-map.svg').'" alt="" aria-hidden="true" loading="lazy">',
            'flag_url' => null,
            'flag_srcset' => null,
            'map_url' => null,
        ];
    }

    private static function iso2(mixed $iso): string
    {
        $iso = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $iso) ?: '', 0, 2));

        return $iso !== '' ? $iso : 'AF';
    }
}
