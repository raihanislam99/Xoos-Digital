<?php
$sizes = [192, 512];
$size = isset($_GET['s']) && in_array((int)$_GET['s'], $sizes) ? (int)$_GET['s'] : 192;
$radius = round($size * 0.208);
$fontSize = round($size * 0.375);
$cx = $size / 2;
$cy = $size * 0.5625;

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=31536000');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$size" height="$size" viewBox="0 0 $size $size">
  <rect width="$size" height="$size" rx="$radius" fill="#6366f1"/>
  <text x="$cx" y="$cy" font-family="Arial,sans-serif" font-size="$fontSize" font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="middle">XT</text>
</svg>
SVG;
