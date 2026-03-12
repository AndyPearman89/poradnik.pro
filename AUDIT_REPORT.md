# PearTree Programmatic Affiliate — Full Audit Report

## 1) Architecture Overview
- Plugin posiada warstwy: `Core`, `Affiliate`, `SEO`, `Frontend`, `Admin`, `Adsense`.
- Obecna struktura była częściowo zgodna z DDD/Clean Architecture, ale brakowało warstwy `Rest` i centralnego dashboardu operacyjnego.
- Bootstrapping i DI są realizowane przez `src/Core/ServiceProvider.php`.
- Dane są persystowane w tabelach:
  - `wp_peartree_affiliate_products`
  - `wp_peartree_affiliate_clicks`
  - `wp_peartree_affiliate_keywords`
  - `wp_peartree_seo_pages`

## 2) Code Smells / Structural Findings
- Brak wydzielonych kontrolerów REST (dodano w refaktorze).
- Część UI admin była niespójna językowo i nawigacyjnie (PL/EN mix).
- `SeoPageRepository::all()` usuwał transient, ale nie ustawiał cache (naprawione).
- Dashboard admin nie zawierał pełnego „overview + module health + recent activity” (przebudowane).
- Menu admin nie miało pełnej struktury enterprise (`Dashboard`, `Adsense`, `Settings`, `Tools`) (naprawione).

## 3) Dead Code / Duplicates
- Powtarzalne wzorce pobierania list i liczników bez centralnego agregatora metryk.
- Powtarzalne renderowanie tabel statystycznych bez warstwy dashboardowej.
- Nieużyty wzorzec transientu dla SEO pages (`ppae_seo_pages_all`) przed poprawką.

## 4) Security Audit
### Verified Good
- Nonce w operacjach admin (`save`, `delete`, `tools actions`).
- `current_user_can('manage_options')` na stronach admin.
- Sanitization/escaping w większości flow (`sanitize_text_field`, `sanitize_title`, `esc_url_raw`, `esc_html`, `esc_attr`).
- SQL prepared statements dla parametrów dynamicznych.

### Risks / Recommendations
- Dynamiczne query na tabelach są poprawne, ale warto w długim terminie dodać statyczną warstwę SQL helpers.
- Przy rozszerzeniu REST na publiczne endpointy wymagane rate-limit + nonce/JWT strategy.

## 5) Performance Audit
### Issues
- Brak paginacji w listach repozytoriów (produkty/SEO pages) dla API.
- Brak cache dla pełnej listy SEO pages.

### Implemented Improvements
- Dodano paginację:
  - `AffiliateRepository::getProductsPaginated()`
  - `SeoPageRepository::allPaginated()`
- Dodano cache dla `SeoPageRepository::all()`.
- Dodano metryki i recent activity z cache:
  - `AffiliateRepository::getOverviewMetrics()`
  - `AffiliateRepository::getRecentActivity()`

## 6) Database Audit
### Existing Schema Findings
- Tabele mają podstawowe indeksy (`slug`, `category`, `date`, `product_id`).
- Brak formalnych FK (typowe dla WP), relacje utrzymywane aplikacyjnie.
- `clicks` i `keywords` korzystają z właściwych typów liczbowych.

### Recommendations
- Dodać indeks złożony `(product_id, date)` w tabeli kliknięć dla raportów czasowych.
- Rozważyć tabelę eventów aktywności dla pełnego activity feed (SaaS telemetry).

## 7) Hook/Cron/REST/Shortcode Audit
- Rewrite/redirect click tracking: `ppae_go_slug` + `template_redirect`.
- Shortcodes: `peartree_adsense`, `peartree_affiliate_box`, `peartree_comparison`, `peartree_ranking`, `peartree_seo_page`.
- Cron: brak dedykowanych eventów w tym pluginie (integracja przez ekosystem).
- REST: dodano bezpieczne endpointy admin-only (`ppae/v1/*`).

## 8) Refactor Implemented (This Delivery)
- Dodano warstwę REST:
  - `src/Rest/StatsController.php`
  - `src/Rest/CatalogController.php`
- Dodano enterprise admin modules:
  - `src/Admin/DashboardPage.php`
  - `src/Admin/ToolsPage.php`
- Przebudowano menu admin do układu SaaS-ready.
- Dodano metryki overview, recent activity, paginację i cache.
- Ujednolicono UI/UX i etykiety PL w panelach administracyjnych.

## 9) Backward Compatibility
- Zachowane istniejące slugi i główne endpointy nawigacyjne (`ppae-dashboard`, `ppae-products`, `ppae-keywords`, `ppae-seo-pages`, `ppae-statistics`).
- Zachowane istniejące shortcode API.
- Dane historyczne i migracje (`DataMigrator`) pozostają kompatybilne.

## 10) Remaining Recommendations (Next Iteration)
- Dodać endpointy write-side REST (POST/PUT/DELETE) z capability map i audit trail.
- Dodać testy integracyjne dla repozytoriów + REST controllers.
- Dodać background jobs dla cięższych analiz/raportów.
- Dodać feature flags i tenant scoping (etap SaaS multi-tenant).
