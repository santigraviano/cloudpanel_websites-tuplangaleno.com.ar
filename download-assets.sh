#!/usr/bin/env bash
# Run this script once to download all images from the original WordPress site.
# Usage: bash download-assets.sh
# Run from the project root (where this file lives).

set -e

BASE="https://tuplangaleno.com.ar/wp-content/uploads"
OUT="public/images"

mkdir -p "$OUT/sanatorios"

echo "Downloading logo and favicon..."
curl -sL "$BASE/2022/10/tuplangaleno-logo.svg"        -o "$OUT/logo.svg"
curl -sL "$BASE/2022/10/cropped-nYDtRnH-192x192.png"  -o "$OUT/favicon.png"

echo "Downloading hero images..."
curl -sL "$BASE/2022/10/pexels-vlada-karpovich-4609046-scaled.jpg" -o "$OUT/hero-1.jpg"
curl -sL "$BASE/2022/10/pexels-gustavo-fring-4148842-scaled.jpg"   -o "$OUT/hero-2.jpg"
curl -sL "$BASE/2022/10/pexels-vlada-karpovich-4617316-scaled.jpg" -o "$OUT/hero-3.jpg"

echo "Downloading sanatorio images..."
curl -sL "$BASE/2022/10/trinidadquilmes.png"                  -o "$OUT/sanatorios/trinidad-quilmes.png"
curl -sL "$BASE/2022/10/trinidadramos.png"                    -o "$OUT/sanatorios/trinidad-ramos.png"
curl -sL "$BASE/2022/10/trinidadmitre.png"                    -o "$OUT/sanatorios/trinidad-mitre.png"
curl -sL "$BASE/2023/05/home_sanatorios_TP_new.png"           -o "$OUT/sanatorios/trinidad-palermo.png"
curl -sL "$BASE/2023/05/home_sanatorios_TSI_fleming.png"      -o "$OUT/sanatorios/trinidad-san-isidro.png"
curl -sL "$BASE/2023/05/Screenshot_10-Custom.png"             -o "$OUT/sanatorios/galeno-barrio-norte.png"
curl -sL "$BASE/2023/05/Dupu_carrusel_01-Custom.jpg"          -o "$OUT/sanatorios/dupuytren-almagro.jpg"

echo "Done! All assets saved to $OUT/"
