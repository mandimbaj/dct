<?php

namespace App\Support;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CountryContext
{
    /**
     * ISO-3166 alpha-3 to alpha-2 mapping used by flag providers and map assets.
     *
     * The warehouse sometimes stores country identifiers as alpha-3 values
     * (SEN, CPV, TCD). Taking the first two letters would point to another
     * country, so topbar flags and country maps must normalize through this
     * explicit list first.
     *
     * @var array<string, string>
     */
    private const ISO3_TO_ISO2 = [
        'AGO' => 'AO',
        'BDI' => 'BI',
        'BEN' => 'BJ',
        'BFA' => 'BF',
        'BWA' => 'BW',
        'CAF' => 'CF',
        'CIV' => 'CI',
        'CMR' => 'CM',
        'COD' => 'CD',
        'COG' => 'CG',
        'COM' => 'KM',
        'CPV' => 'CV',
        'DZA' => 'DZ',
        'ERI' => 'ER',
        'ETH' => 'ET',
        'GAB' => 'GA',
        'GHA' => 'GH',
        'GIN' => 'GN',
        'GMB' => 'GM',
        'GNB' => 'GW',
        'GNQ' => 'GQ',
        'KEN' => 'KE',
        'LBR' => 'LR',
        'LSO' => 'LS',
        'MDG' => 'MG',
        'MLI' => 'ML',
        'MOZ' => 'MZ',
        'MRT' => 'MR',
        'MUS' => 'MU',
        'MWI' => 'MW',
        'NAM' => 'NA',
        'NER' => 'NE',
        'NGA' => 'NG',
        'RWA' => 'RW',
        'SEN' => 'SN',
        'SLE' => 'SL',
        'SSD' => 'SS',
        'STP' => 'ST',
        'SWZ' => 'SZ',
        'SYC' => 'SC',
        'TCD' => 'TD',
        'TGO' => 'TG',
        'TZA' => 'TZ',
        'UGA' => 'UG',
        'ZAF' => 'ZA',
        'ZMB' => 'ZM',
        'ZWE' => 'ZW',
    ];

    /**
     * Lightweight country identity for compact UI areas such as the topbar.
     *
     * Unlike forUser(), this does not include the country map SVG.
     *
     * @return array<string, mixed>|null
     */
    public static function identityForUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        if ($user->canViewAllCountries()) {
            return self::africaIdentityPayload();
        }

        $country = self::countryForUser($user);

        if (! $country instanceof Country) {
            return null;
        }

        return Cache::remember(
            'country-context-identity.v2.'.$country->getKey().'.'.WarehouseLocale::current(),
            now()->addHours(12),
            fn (): array => self::identityPayload($country),
        );
    }

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

        $country = self::countryForUser($user);

        if (! $country instanceof Country) {
            return null;
        }

        return Cache::remember(
            'country-context-card.simple.v2.'.$country->getKey().'.'.WarehouseLocale::current(),
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

        return [
            'name' => $country->display_name,
            'iso' => strtoupper($iso),
            'svg_html' => CountryMapData::buildSvgHtml($iso),
            ...self::flagPayload($iso),
            'map_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function identityPayload(Country $country): array
    {
        $iso = self::iso2($country->iso_alpha);

        return [
            'name' => $country->display_name,
            'iso' => strtoupper($iso),
            ...self::flagPayload($iso),
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

    /**
     * @return array<string, mixed>
     */
    private static function africaIdentityPayload(): array
    {
        return [
            'name' => __('aho.country_context.africa_region'),
            'iso' => 'AFRO',
            'flag_url' => null,
            'flag_srcset' => null,
        ];
    }

    private static function countryForUser(User $user): ?Country
    {
        if (blank($user->location_id)) {
            return null;
        }

        $country = $user->relationLoaded('location')
            ? $user->location
            : $user->location()->with('translations')->first();

        if (! $country instanceof Country) {
            return null;
        }

        return (int) $country->locationlevel_id === 2 ? $country : null;
    }

    /**
     * @return array<string, string|null>
     */
    private static function flagPayload(string $iso): array
    {
        $iso = strtolower(self::iso2($iso));

        return [
            'flag_url' => "https://flagcdn.com/w40/{$iso}.png",
            'flag_srcset' => "https://flagcdn.com/w80/{$iso}.png 2x",
        ];
    }

    private static function iso2(mixed $iso): string
    {
        $iso = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $iso) ?: '');

        if ($iso === '') {
            return 'AF';
        }

        if (strlen($iso) === 2) {
            return $iso;
        }

        if (isset(self::ISO3_TO_ISO2[$iso])) {
            return self::ISO3_TO_ISO2[$iso];
        }

        $iso3 = substr($iso, 0, 3);

        if (isset(self::ISO3_TO_ISO2[$iso3])) {
            return self::ISO3_TO_ISO2[$iso3];
        }

        $iso = substr($iso, 0, 2);

        return $iso !== '' ? $iso : 'AF';
    }
}
