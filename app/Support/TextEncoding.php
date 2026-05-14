<?php

namespace App\Support;

use Illuminate\Support\Str;

class TextEncoding
{
    public static function clean(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $text = static::ensureUtf8($value);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (! static::looksMojibaked($text)) {
                break;
            }

            $candidate = static::ensureUtf8(mb_convert_encoding($text, 'Windows-1252', 'UTF-8'));

            if ($candidate === '' || $candidate === $text || ! mb_check_encoding($candidate, 'UTF-8')) {
                break;
            }

            $text = $candidate;
        }

        $text = static::ensureUtf8($text);
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private static function ensureUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
            $converted = mb_convert_encoding($value, 'UTF-8', $encoding);

            if ($converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private static function looksMojibaked(string $value): bool
    {
        return preg_match('/Ã[\x{0080}-\x{00BF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{201A}-\x{2026}\x{2030}\x{2039}\x{203A}\x{2122}]/u', $value) === 1
            || preg_match('/Â[\x{0080}-\x{00BF}]/u', $value) === 1
            || Str::contains($value, [
                'â€',
                'â€™',
                'â€œ',
                'â€˜',
                'â€“',
                'â€”',
                '�',
            ]);
    }
}
