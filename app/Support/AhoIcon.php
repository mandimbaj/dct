<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

final class AhoIcon
{
    public const API_TOKENS = 'api-tokens';

    public const AUTHENTICATION = 'authentication';

    public const DATA_ELEMENTS = 'data-elements';

    public const DATA_INTEGRATION = 'data-integration';

    public const DATA_QUALITY = 'data-quality';

    public const FACILITIES = 'facilities';

    public const HEALTH_SERVICES = 'health-services';

    public const HEALTH_WORKFORCE = 'health-workforce';

    public const INDICATORS = 'indicators';

    public const NATIONAL_OBSERVATORY = 'national-observatory';

    public const PUBLICATIONS = 'publications';

    public const REGIONS = 'regions';

    public const UHC_CLOCK = 'uhc-clock';

    /**
     * Application-owned icon registry. These values are deliberately kept in
     * code so navigation rendering never depends on a warehouse query.
     *
     * @var array<string, string>
     */
    private const ICONS = [
        self::API_TOKENS => 'fa-solid fa-key',
        self::AUTHENTICATION => 'fa-solid fa-user-shield',
        self::DATA_ELEMENTS => 'fa-solid fa-database',
        self::DATA_INTEGRATION => 'fa-solid fa-link',
        self::DATA_QUALITY => 'fa-solid fa-clipboard-check',
        self::FACILITIES => 'fa-solid fa-hospital',
        self::HEALTH_SERVICES => 'fa-solid fa-heart-pulse',
        self::HEALTH_WORKFORCE => 'fa-solid fa-user-doctor',
        self::INDICATORS => 'fa-solid fa-chart-line',
        self::NATIONAL_OBSERVATORY => 'fa-solid fa-house-medical',
        self::PUBLICATIONS => 'fa-solid fa-book-open',
        self::REGIONS => 'fa-solid fa-earth-africa',
        self::UHC_CLOCK => 'fa-solid fa-clock',
    ];

    public static function make(string $name): HtmlString
    {
        $classes = self::ICONS[$name] ?? 'fa-solid fa-gear';

        return new HtmlString(
            '<i class="aho-custom-icon '.e($classes).'" aria-hidden="true"></i>'
        );
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::ICONS;
    }
}
