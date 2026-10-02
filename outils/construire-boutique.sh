#!/usr/bin/env bash
# Construit dist/comptoir-boutique-<version>.zip, le paquet a televerser
# dans WordPress (Extensions > Ajouter > Televerser, ou par le connecteur).
#
# Le JavaScript et le CSS sont minifies pour le paquet : la source lisible,
# commentee, reste dans comptoir-boutique/ et c'est elle qu'on modifie.
# Le JavaScript sort en ECMAScript 5, comme la source : il doit tourner sur
# les telephones Android d'entree de gamme.
#
# Usage : outils/construire-boutique.sh   (depuis n'importe ou dans le depot)
set -euo pipefail

RACINE="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$RACINE/comptoir-boutique"
VERSION="$(sed -n "s/^define( 'CPB_VERSION', '\([^']*\)' );$/\1/p" "$SRC/comptoir-boutique.php")"
[ -n "$VERSION" ] || { echo "Version introuvable dans comptoir-boutique.php" >&2; exit 1; }

TRAVAIL="$(mktemp -d)"
trap 'rm -rf "$TRAVAIL"' EXIT
mkdir -p "$TRAVAIL/comptoir-boutique"

php -l "$SRC/comptoir-boutique.php" >/dev/null
cp "$SRC/comptoir-boutique.php" "$TRAVAIL/comptoir-boutique/"
npx --yes terser@5 "$SRC/boutique.js" --ecma 5 --compress --mangle --comments false \
  -o "$TRAVAIL/comptoir-boutique/boutique.js"
npx --yes lightningcss-cli@1 --minify "$SRC/boutique.css" -o "$TRAVAIL/comptoir-boutique/boutique.css"
node --check "$TRAVAIL/comptoir-boutique/boutique.js"

mkdir -p "$RACINE/dist"
ZIP="$RACINE/dist/comptoir-boutique-$VERSION.zip"
rm -f "$ZIP"
( cd "$TRAVAIL" && zip -q -9 -r -X "$ZIP" comptoir-boutique )
unzip -tq "$ZIP" >/dev/null
echo "$ZIP"
ls -l "$TRAVAIL/comptoir-boutique"
