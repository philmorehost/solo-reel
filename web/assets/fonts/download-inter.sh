#!/usr/bin/env bash
# Downloads the Inter font family (weights 400/500/600/700/800, latin subset,
# woff2) for self-hosting. Source: @fontsource/inter, a widely-used OFL-licensed
# repackaging of Google's Inter font distributed as real static per-weight
# files (the fonts.googleapis.com CSS API does not reliably return distinct
# per-weight files to non-browser clients like curl/wget).
set -euo pipefail
cd "$(dirname "$0")"

FONTSOURCE_VERSION="5.0.16"
BASE_URL="https://cdn.jsdelivr.net/npm/@fontsource/inter@${FONTSOURCE_VERSION}/files"

for weight in 400 500 600 700 800; do
  echo "Downloading Inter ${weight}..."
  curl -fsSL "${BASE_URL}/inter-latin-${weight}-normal.woff2" -o "inter-${weight}.woff2"
done

echo "Done. Files written to $(pwd):"
ls -la inter-*.woff2
