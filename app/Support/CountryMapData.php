<?php

namespace App\Support;

class CountryMapData
{
    /**
     * Load an ammap SVG file, apply a solid fill colour, add viewBox, and return clean SVG markup.
     */
    public static function buildSvgHtml(string $iso, string $color = '#0093D5'): ?string
    {
        $iso = strtoupper($iso);
        $file = public_path('images/country-maps/'.$iso.'.svg');

        if (! file_exists($file)) {
            return null;
        }

        $svg = file_get_contents($file);

        if (empty($svg)) {
            return null;
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($svg, "\xEF\xBB\xBF")) {
            $svg = substr($svg, 3);
        }

        // Compute bounding box by scanning all path d="" attributes
        preg_match_all('/\bd="([^"]+)"/', $svg, $pathMatches);

        $minX = PHP_FLOAT_MAX;
        $minY = PHP_FLOAT_MAX;
        $maxX = -PHP_FLOAT_MAX;
        $maxY = -PHP_FLOAT_MAX;

        foreach ($pathMatches[1] as $d) {
            preg_match_all('/(-?[\d.]+),(-?[\d.]+)/', $d, $coords, PREG_SET_ORDER);
            foreach ($coords as $coord) {
                $x = (float) $coord[1];
                $y = (float) $coord[2];
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($minX === PHP_FLOAT_MAX) {
            return null;
        }

        $pad = max($maxX - $minX, $maxY - $minY) * 0.04;
        $vbX = $minX - $pad;
        $vbY = $minY - $pad;
        $vbW = ($maxX - $minX) + 2 * $pad;
        $vbH = ($maxY - $minY) + 2 * $pad;
        $viewBox = sprintf('%.1f %.1f %.1f %.1f', $vbX, $vbY, $vbW, $vbH);

        // Add viewBox + preserveAspectRatio to the <svg> opening tag
        $svg = preg_replace(
            '/<svg\b/',
            '<svg viewBox="'.$viewBox.'" preserveAspectRatio="xMidYMid meet"',
            $svg,
            1
        );

        // Apply solid fill colour and soften subdivision stroke lines
        $safeColor = htmlspecialchars($color, ENT_XML1);
        $svg = str_replace('fill: #CCCCCC', 'fill: '.$safeColor, $svg);
        $svg = str_replace('stroke:white', 'stroke: rgba(255,255,255,0.45)', $svg);

        // Strip XML declaration, amcharts namespace attribute, and ammap element
        $svg = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg);
        $svg = preg_replace('/\s+xmlns:amcharts="[^"]*"/', '', $svg);
        $svg = preg_replace('/<amcharts:ammap[^>]*>.*?<\/amcharts:ammap>/s', '', $svg);
        $svg = preg_replace('/<amcharts:ammap[^\/]*\/>/', '', $svg);
        // Remove ammap comments
        $svg = preg_replace('/<!--\s*\(?{id:.*?-->/s', '', $svg);

        return trim($svg);
    }
}
