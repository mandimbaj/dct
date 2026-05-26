<?php
/**
 * One-time script: converts Africa_adm0.json (TopoJSON) to a blue SVG
 * and saves it to public/images/africa-map.svg
 *
 * Run: php scripts/generate-africa-svg.php
 */

$jsonFile = 'C:/Users/mandimbaj/OneDrive - World Health Organization/AHO/Formation Power BI/Africa_adm0.json';
$outFile  = __DIR__.'/../public/images/africa-map.svg';
$color    = '#0093D5';

$data = json_decode(file_get_contents($jsonFile), true);

$scale     = $data['transform']['scale'];
$translate = $data['transform']['translate'];

// Decode all arcs: delta → geographic (lon, lat)
$arcs = [];
foreach ($data['arcs'] as $rawArc) {
    $x = 0; $y = 0;
    $pts = [];
    foreach ($rawArc as $delta) {
        $x += $delta[0];
        $y += $delta[1];
        $pts[] = [$x * $scale[0] + $translate[0], $x * $scale[1] + $translate[1]];
        // Correction: use x for lon, y for lat
        array_pop($pts);
        $pts[] = [
            $x * $scale[0] + $translate[0],
            $y * $scale[1] + $translate[1],
        ];
    }
    $arcs[] = $pts;
}

// Find overall bounding box of all decoded points
$minLon = PHP_FLOAT_MAX; $maxLon = -PHP_FLOAT_MAX;
$minLat = PHP_FLOAT_MAX; $maxLat = -PHP_FLOAT_MAX;
foreach ($arcs as $pts) {
    foreach ($pts as [$lon, $lat]) {
        if ($lon < $minLon) $minLon = $lon;
        if ($lon > $maxLon) $maxLon = $lon;
        if ($lat < $minLat) $minLat = $lat;
        if ($lat > $maxLat) $maxLat = $lat;
    }
}

$W = 800;
$H = 900;
$lonRange = $maxLon - $minLon;
$latRange = $maxLat - $minLat;

// Keep aspect ratio, center in viewbox
$scaleX = $W / $lonRange;
$scaleY = $H / $latRange;
$s = min($scaleX, $scaleY) * 0.96; // 2% padding
$offX = ($W - $lonRange * $s) / 2;
$offY = ($H - $latRange * $s) / 2;

function toSvg(float $lon, float $lat, float $minLon, float $maxLat, float $s, float $offX, float $offY): string {
    $x = ($lon - $minLon) * $s + $offX;
    $y = ($maxLat - $lat) * $s + $offY; // Y flipped
    return round($x, 1).','.round($y, 1);
}

// Build arc ring path segments
function ringPath(array $ringArcIndices, array $arcs, float $minLon, float $maxLat, float $s, float $offX, float $offY): string {
    $d = '';
    foreach ($ringArcIndices as $idx) {
        $reversed = $idx < 0;
        $pts = $arcs[$reversed ? ~$idx : $idx];
        if ($reversed) {
            $pts = array_reverse($pts);
        }
        foreach ($pts as $i => [$lon, $lat]) {
            $coord = toSvg($lon, $lat, $minLon, $maxLat, $s, $offX, $offY);
            $d .= ($i === 0 && $d === '' ? 'M' : ($i === 0 ? 'M' : 'L')).$coord;
        }
    }
    return $d !== '' ? $d.'Z' : '';
}

$geoms = $data['objects']['Africa']['geometries'];

$paths = '';
foreach ($geoms as $geom) {
    $type = $geom['type'];
    $polygonSets = $type === 'MultiPolygon' ? $geom['arcs'] : [$geom['arcs']];

    foreach ($polygonSets as $polygon) {
        $d = '';
        foreach ($polygon as $ring) {
            $d .= ringPath($ring, $arcs, $minLon, $maxLat, $s, $offX, $offY);
        }
        if ($d !== '') {
            $paths .= '<path d="'.$d.'"/>';
        }
    }
}

$safeColor = htmlspecialchars($color, ENT_XML1);

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$W} {$H}" preserveAspectRatio="xMidYMid meet">
<g fill="{$safeColor}" stroke="rgba(255,255,255,0.45)" stroke-width="0.8" stroke-linejoin="round">
{$paths}
</g>
</svg>
SVG;

file_put_contents($outFile, $svg);
echo "Written to: $outFile\n";
echo "File size: ".strlen($svg)." bytes\n";
