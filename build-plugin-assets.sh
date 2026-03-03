#!/usr/bin/env bash
# Copy FAQ plugin files from "FAQ Management Plugin" folder into Tool-Installer/plugin-assets.
# Run from Tool-Installer: ./build-plugin-assets.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FAQ_SOURCE="$SCRIPT_DIR/../FAQ Management Plugin"
OUT_DIR="$SCRIPT_DIR/plugin-assets"
mkdir -p "$OUT_DIR"

cp "$FAQ_SOURCE/admin/FAQ Management Plugin.php" "$OUT_DIR/admin.php" 2>/dev/null || true
cp "$FAQ_SOURCE/admin/FAQ Management Plugin.css" "$OUT_DIR/admin.css" 2>/dev/null || true
cp "$FAQ_SOURCE/frontend/FAQ Global Renderer.php" "$OUT_DIR/global-renderer.php" 2>/dev/null || true

echo "Done. Plugin assets in $OUT_DIR"
