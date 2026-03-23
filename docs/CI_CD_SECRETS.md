# CI/CD Secrets for Production Deploy

Set these secrets in GitHub repository settings (`Settings -> Secrets and variables -> Actions`).

## Required
- `SSH_HOST` - Production server hostname or IP
- `SSH_USER` - SSH user used for deployment
- `SSH_KEY` - Private SSH key (ed25519/rsa) with deploy access
- `DEPLOY_SOURCE_DIR` - Remote path where repository is synced, e.g. `/opt/poradnik-pro`
- `WP_CONTENT_DIR` - Remote WordPress content path, e.g. `/var/www/html/wp-content`
- `PROD_BASE_URL` - Public base URL for post-deploy checks, e.g. `https://poradnik.pro`

## Optional (recommended)
- `SSH_PORT` - SSH port (default: `22`)
- `MU_PLUGIN_DIR` - Overrides default `wp-content/mu-plugins/platform-core`
- `MU_LOADER_FILE` - Overrides default `wp-content/mu-plugins/poradnik-platform-loader.php`
- `THEME_DIR` - Overrides default `wp-content/themes/generatepress-child-poradnik`
- `WP_BIN` - WP-CLI binary path (default: `wp`)
- `RUN_MIGRATIONS` - `1`/`0` (default: `1`)
- `FLUSH_CACHE` - `1`/`0` (default: `1`)
