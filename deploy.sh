#!/usr/bin/env bash
set -euo pipefail

echo "[deploy] Starting Poradnik.pro deployment"

DEPLOY_SOURCE_DIR="${DEPLOY_SOURCE_DIR:-$PWD}"
WP_CONTENT_DIR="${WP_CONTENT_DIR:-/var/www/html/wp-content}"
MU_PLUGIN_DIR="${MU_PLUGIN_DIR:-$WP_CONTENT_DIR/mu-plugins/platform-core}"
MU_LOADER_FILE="${MU_LOADER_FILE:-$WP_CONTENT_DIR/mu-plugins/poradnik-platform-loader.php}"
THEME_DIR="${THEME_DIR:-$WP_CONTENT_DIR/themes/generatepress-child-poradnik}"
PHP_BIN="${PHP_BIN:-php}"
WP_BIN="${WP_BIN:-wp}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-1}"
FLUSH_CACHE="${FLUSH_CACHE:-1}"

required_files=(
  "$DEPLOY_SOURCE_DIR/backend"
  "$DEPLOY_SOURCE_DIR/theme"
  "$DEPLOY_SOURCE_DIR/poradnik-platform-loader.php"
)

for path in "${required_files[@]}"; do
  if [[ ! -e "$path" ]]; then
    echo "[deploy][error] Missing required path: $path"
    exit 1
  fi
done

echo "[deploy] Ensuring target directories exist"
mkdir -p "$MU_PLUGIN_DIR" "$THEME_DIR"

RSYNC_FLAGS=(
  -az
  --delete
  --delete-delay
  --delay-updates
  --compress
)

echo "[deploy] Sync backend -> $MU_PLUGIN_DIR"
rsync "${RSYNC_FLAGS[@]}" "$DEPLOY_SOURCE_DIR/backend/" "$MU_PLUGIN_DIR/"

echo "[deploy] Sync theme -> $THEME_DIR"
rsync "${RSYNC_FLAGS[@]}" "$DEPLOY_SOURCE_DIR/theme/" "$THEME_DIR/"

echo "[deploy] Update MU loader -> $MU_LOADER_FILE"
install -m 0644 "$DEPLOY_SOURCE_DIR/poradnik-platform-loader.php" "$MU_LOADER_FILE"

if [[ "$RUN_MIGRATIONS" == "1" ]]; then
  echo "[deploy] Running database migrations"
  "$WP_BIN" eval 'Poradnik\Platform\Infrastructure\Database\Migrator::maybeMigrate();' --allow-root
fi

if [[ "$FLUSH_CACHE" == "1" ]]; then
  echo "[deploy] Flushing WordPress cache + rewrite"
  "$WP_BIN" cache flush --allow-root || true
  "$WP_BIN" rewrite flush --hard --allow-root || true
fi

echo "[deploy] PHP syntax check (critical files)"
"$PHP_BIN" -l "$MU_PLUGIN_DIR/Api/Controllers/AiContentController.php"
"$PHP_BIN" -l "$MU_PLUGIN_DIR/Api/Controllers/AiImageController.php"
"$PHP_BIN" -l "$MU_PLUGIN_DIR/Api/Controllers/ProgrammaticBuildController.php"

echo "[deploy] Deployment completed successfully"
