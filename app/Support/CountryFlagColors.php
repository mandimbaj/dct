<?php

namespace App\Support;

class CountryFlagColors
{
    /** @var array<string, list<string>> */
    private static array $colors = [
        // Format: [primary, secondary, tertiary]  (top → bottom of flag)
        'DZ' => ['#006233', '#FFFFFF', '#D21034'],
        'AO' => ['#CC0000', '#000000', '#FFCC00'],
        'BJ' => ['#008751', '#FCD116', '#E8112D'],
        'BW' => ['#75AADB', '#FFFFFF', '#000000'],
        'BF' => ['#EF2B2D', '#009A00', '#FCD116'],
        'BI' => ['#CE1126', '#1EB53A', '#FFFFFF'],
        'CV' => ['#003893', '#CF2027', '#F7D116'],
        'CM' => ['#007A5E', '#CE1126', '#FCD116'],
        'CF' => ['#289728', '#FFFFFF', '#BC0026'],
        'TD' => ['#002664', '#FECB00', '#C60C30'],
        'KM' => ['#3A75C4', '#009A44', '#FFFFFF'],
        'CG' => ['#009543', '#FBDE4A', '#DC241F'],
        'CD' => ['#007FFF', '#F7D618', '#CE1126'],
        'CI' => ['#F77F00', '#FFFFFF', '#009A44'],
        'GQ' => ['#3E9A00', '#FFFFFF', '#E32118'],
        'ER' => ['#12AD2B', '#EF1C24', '#4189DD'],
        'SZ' => ['#3E5EB9', '#FFD900', '#B10C0C'],
        'ET' => ['#078930', '#FCDD09', '#DA121A'],
        'GA' => ['#009E60', '#FCD116', '#003189'],
        'GM' => ['#3A7728', '#CF0921', '#FFFFFF'],
        'GH' => ['#006B3F', '#FCD116', '#EF3340'],
        'GN' => ['#CE1126', '#FCD116', '#009460'],
        'GW' => ['#CE1126', '#FCD116', '#009460'],
        'KE' => ['#006600', '#BB0000', '#000000'],
        'LS' => ['#009A44', '#FFFFFF', '#00209F'],
        'LR' => ['#BF0A30', '#FFFFFF', '#002868'],
        'MG' => ['#FC3D32', '#007E3A', '#FFFFFF'],
        'MW' => ['#000000', '#CE1126', '#339E35'],
        'ML' => ['#14B53A', '#FCD116', '#CE1126'],
        'MR' => ['#006233', '#FFD700', '#D01C1F'],
        'MU' => ['#EA2839', '#1A206D', '#EE6F30'],
        'MZ' => ['#009A44', '#000000', '#FCE100'],
        'NA' => ['#009FCA', '#EF7D00', '#003580'],
        'NE' => ['#E05206', '#FFFFFF', '#0DB02B'],
        'NG' => ['#008751', '#FFFFFF', '#008751'],
        'RW' => ['#20603D', '#FAD201', '#00A1DE'],
        'ST' => ['#12AD2B', '#FFCE00', '#D21034'],
        'SN' => ['#00853F', '#FDEF42', '#E31B23'],
        'SC' => ['#003F87', '#FCD856', '#D62828'],
        'SL' => ['#1EB53A', '#FFFFFF', '#0072C6'],
        'ZA' => ['#007A4D', '#FFB612', '#DE3831'],
        'SS' => ['#078930', '#000000', '#CE1126'],
        'TZ' => ['#1EB53A', '#FCD116', '#009BDE'],
        'TG' => ['#006A4E', '#FFCE00', '#D21034'],
        'UG' => ['#000000', '#FCDC04', '#DE3108'],
        'ZM' => ['#198A00', '#EF0000', '#000000'],
        'ZW' => ['#006400', '#FFD200', '#D40000'],
    ];

    /** @return list<string> */
    public static function get(string $iso): array
    {
        return self::$colors[strtoupper($iso)] ?? ['#0093D5'];
    }
}
