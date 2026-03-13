# PORADNIK.PRO – Platform MU-Plugins

**Poradnik.PRO** is a scalable SEO knowledge portal and monetisation platform built on WordPress MU-plugins.

## Stack

- WordPress 6+
- PHP 8.1+
- GeneratePress Child Theme
- ACF Pro
- Stripe API
- REST API + AJAX
- Schema.org

## Architecture

Critical business logic runs in `mu-plugins` to ensure stability independently of standard plugin activation.

```
wp-content/mu-plugins/
├── poradnik-platform-loader.php   # MU bootstrap entry point
└── platform-core/
    ├── Core/          # Bootstrap, ModuleRegistry, Runtime, EventLogger, Capabilities
    ├── Admin/         # WP Admin pages (module flags, Stripe settings, dashboards)
    ├── Api/           # REST controllers (RestKernel + domain controllers)
    ├── Domain/        # Business entities and services
    ├── Infrastructure/# Database migrations
    └── Modules/       # Feature modules (Affiliate, Rankings, Reviews, Ads, …)
```

## Feature Modules

| Module              | Status |
|---------------------|--------|
| ContentModel (CPT)  | ✅     |
| Affiliate Engine    | ✅     |
| Ranking Engine      | ✅     |
| Review Engine       | ✅     |
| Ad Marketplace      | ✅     |
| Sponsored Articles  | ✅     |
| Advertiser Dashboard| ✅     |
| Stripe Payments     | ✅     |
| SEO Automation      | ✅     |
| Programmatic SEO    | ✅     |
| AI Content          | ✅     |
| AI Image            | ✅     |

## Installation

1. Copy `poradnik-platform-loader.php` and `platform-core/` into your WordPress `wp-content/mu-plugins/` directory.
2. (Optional) Run `composer install` to generate the autoloader.
3. Flush WordPress permalinks: **WP Admin → Settings → Permalinks → Save Changes**.
4. Configure modules: **WP Admin → Tools → Poradnik Platform Modules**.
5. Add Stripe credentials: **WP Admin → Settings → Stripe Settings**.

## Configuration

### Environment variables / WP options

| Option key                        | Description                         |
|-----------------------------------|-------------------------------------|
| `poradnik_stripe_secret_key`      | Stripe secret key (`sk_…`)          |
| `poradnik_stripe_publishable_key` | Stripe publishable key (`pk_…`)     |
| `poradnik_stripe_webhook_secret`  | Stripe webhook signing secret       |
| `poradnik_platform_module_flags`  | JSON map of enabled/disabled modules|
| `poradnik_platform_db_version`    | Current DB schema version           |

Copy `.env.example` to `.env.local` and document your environment values alongside your deployment notes.

## REST API (minimum)

| Method | Endpoint                                  |
|--------|-------------------------------------------|
| GET    | `/wp-json/poradnik/v1/health`             |
| POST   | `/wp-json/poradnik/v1/affiliate/click`    |
| POST   | `/wp-json/poradnik/v1/ads/click`          |
| POST   | `/wp-json/poradnik/v1/ads/impression`     |
| POST   | `/wp-json/poradnik/v1/sponsored/orders`   |
| GET    | `/wp-json/poradnik/v1/dashboard/statistics`|
| POST   | `/wp-json/poradnik/v1/ai/content/generate`|
| POST   | `/wp-json/poradnik/v1/ai/image/generate`  |
| POST   | `/wp-json/poradnik/v1/seo/programmatic/build`|

## Development

### PHP lint

```bash
find platform-core -name "*.php" | xargs -I{} php -l {}
```

### Code standards

```bash
composer install
vendor/bin/phpcs
```

## Database

The `Infrastructure/Database/Migrator.php` runner creates all required tables idempotently on plugin bootstrap using `dbDelta`.

Tables: `affiliate_products`, `affiliate_clicks`, `affiliate_categories`, `ad_campaigns`, `ad_slots`, `ad_clicks`, `ad_impressions`, `sponsored_articles`.

## Changelog

See [PORADNIK-PRO-IMPLEMENTATION-CHECKLIST.md](PORADNIK-PRO-IMPLEMENTATION-CHECKLIST.md) for sprint progress.

## License

Proprietary – all rights reserved by Poradnik.PRO.
