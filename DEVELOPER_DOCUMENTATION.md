# Developer Documentation — peartree.pro Programmatic Affiliate

## Architecture

```text
src/
  Core/
    Kernel.php
    ServiceProvider.php
    DataMigrator.php
  Affiliate/
    Domain/
    Application/
    Infrastructure/
  SEO/
    Domain/
    Application/
    Infrastructure/
  Adsense/
  Admin/
    AdminMenu.php
    DashboardPage.php
    SettingsPage.php
    ProductsPage.php
    KeywordsPage.php
    SeoPagesPage.php
    StatisticsPage.php
    ToolsPage.php
  Frontend/
  Rest/
    StatsController.php
    CatalogController.php
assets/
templates/
```

## Module Responsibilities
- `Core`: bootstrap, rewrite rules, module wiring, migration.
- `Affiliate`: produkty, keywordy, trackowanie kliknięć, statystyki.
- `SEO`: model stron SEO i generator.
- `Adsense`: ustawienia i renderer reklam.
- `Admin`: panel zarządzania, dashboard, narzędzia maintenance.
- `Frontend`: shortcodes i renderery treści.
- `Rest`: endpointy API admin-only.

## Database Schema
- `wp_peartree_affiliate_products`
  - `id`, `title`, `slug`, `destination_url`, `price`, `rating`, `clicks`, ...
- `wp_peartree_affiliate_clicks`
  - `id`, `product_id`, `date`, `ip`, `referrer`, `user_agent`
- `wp_peartree_affiliate_keywords`
  - `id`, `keyword`, `product_id`, `created_at`
- `wp_peartree_seo_pages`
  - `id`, `keyword`, `slug`, `title`, `content_template`, `wp_page_id`

## WordPress Hooks
- `plugins_loaded`: boot plugin i ładowanie textdomain.
- `init`: rewrite registration.
- `template_redirect`: obsługa redirectów `/go/{slug}`.
- `the_content`: autolink i renderery frontend.
- `admin_menu`: rejestracja panelu admin.
- `admin_init`: settings registration.

## Shortcodes
- `[peartree_adsense placement="article_top"]`
- `[peartree_affiliate_box id="123"]`
- `[peartree_comparison ids="1,2,3"]`
- `[peartree_ranking ids="1,2,3"]`
- `[peartree_seo_page slug="example"]`

## REST API Endpoints
Namespace: `ppae/v1` (admin capability required)
- `GET /wp-json/ppae/v1/stats`
- `GET /wp-json/ppae/v1/products?page=1&per_page=20`
- `GET /wp-json/ppae/v1/seo-pages?page=1&per_page=20`

## Admin Navigation
- Dashboard
- AdSense
- Produkty afiliacyjne
- Słowa kluczowe autolink
- Strony Programmatic SEO
- Statystyki
- Ustawienia
- Narzędzia

## Performance Notes
- Produkty/SEO pages: paginacja w repozytoriach.
- Metryki: cache transient (`ppae_overview_metrics`, `ppae_stats_cache`, `ppae_products_all`, `ppae_seo_pages_all`).
- Cache flush dostępny w `Narzędzia`.

## Security Notes
- Wszystkie strony admin: `current_user_can('manage_options')`.
- Nonce protection dla akcji zapisu i operacji narzędziowych.
- Parametry SQL dynamiczne obsługiwane przez `$wpdb->prepare()`.
