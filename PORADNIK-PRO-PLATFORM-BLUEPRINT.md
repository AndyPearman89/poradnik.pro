# PORADNIK.PRO – PLATFORM GENERATOR BLUEPRINT (MU-PLUGINS)

Data: 12 marca 2026  
Status: Production Blueprint v2 (MU-first)

## 1) Cel platformy
PORADNIK.PRO to skalowalny portal wiedzy SEO + platforma monetyzacji, łącząca:
- portal poradnikowy
- affiliate marketing
- rankingi i recenzje
- marketplace reklamowy
- system artykułów sponsorowanych
- generator treści AI
- generator obrazów AI
- multilingual SEO
- dashboard SaaS dla reklamodawców

Wzorzec produktowy: Wirecutter / NerdWallet / Tooltester / Zapier Blog.

## 2) Stack technologiczny
- WordPress 6+
- PHP 8+
- GeneratePress Child Theme
- ACF Pro
- REST API + AJAX
- Stripe API
- Schema.org
- Google Analytics
- Google Search Console
- AdSense

## 3) Architektura docelowa (MU-first)
Krytyczna logika biznesowa działa w `mu-plugins`, aby zapewnić stabilność niezależnie od aktywacji standardowych pluginów.

Aktualna baza techniczna (wdrożona):
- loader MU (`poradnik-platform-loader.php`)
- `Core/Bootstrap` z autoload
- dynamiczny `ModuleRegistry` + feature flags
- panel `Tools -> Poradnik Platform Modules`
- bootstrapy modułów domenowych

## 4) Główna struktura serwisu
Nawigacja:
- Home
- Poradniki
- Rankingi
- Recenzje
- Porównania
- Narzędzia
- Aktualności

Przykładowe URL:
- `/poradnik/jak-zarabiac-online/`
- `/ranking/najlepszy-hosting-wordpress/`
- `/recenzja/elementor-pro/`
- `/porownanie/ahrefs-vs-semrush/`
- `/news/ai-trendy-2026/`

## 5) Content model (CPT)
Wymagane CPT:
- `guide`
- `ranking`
- `review`
- `comparison`
- `news`
- `tool`
- `sponsored`

Wymagania wspólne dla każdego typu:
- featured image
- author
- reading time
- table of contents
- FAQ
- schema markup
- related articles

Rekomendowane taksonomie współdzielone:
- `topic`
- `intent`
- `stage`
- `industry`

## 6) Moduły domenowe
### 6.1 Affiliate Engine
Funkcje:
- baza produktów afiliacyjnych
- cloaking linków
- product cards
- price boxes
- comparison tables

Shortcodes:
- `[affiliate_product]`
- `[comparison_table]`
- `[top_product]`

Tabele:
- `affiliate_products`
- `affiliate_clicks`
- `affiliate_categories`

Tracking:
- clicks
- traffic source
- CTR

### 6.2 Ranking Engine
Funkcje:
- product cards
- ratings
- pros/cons
- spec tables
- affiliate CTA buttons
- sorting, filters, dynamic scoring

### 6.3 Review Engine
Każda recenzja zawiera:
- rating
- pros
- cons
- verdict
- specification table

Schema:
- `Review`
- `Product`
- `Rating`

### 6.4 Ad Marketplace
Sloty reklamowe:
- Homepage Hero
- Sidebar Banner
- Inline Article Ad
- Footer Banner

Funkcje:
- impressions
- click tracking
- campaign duration
- ad rotation

Tabele:
- `ad_campaigns`
- `ad_slots`
- `ad_clicks`
- `ad_impressions`

### 6.5 Sponsored Article System
Workflow:
1. Advertiser submits article
2. Editor review
3. Payment
4. Publish

Pakiety:
- Basic
- Featured
- Homepage

Funkcje:
- sponsored badge
- nofollow links
- analytics

Tabela:
- `sponsored_articles`

### 6.6 Advertiser SaaS Dashboard
Widoki:
- Overview
- Campaigns
- Statistics
- Payments

Metryki:
- impressions
- clicks
- CTR

Płatności:
- Stripe API (checkout, webhook, status payment intent)

### 6.7 AI Content Generator
Narzędzia:
- headline generator
- article outline generator
- FAQ generator
- meta description generator
- ranking generator

Panel admin:
- AI Article Assistant

### 6.8 AI Image Generator
Generuje:
- featured images
- Open Graph images
- social media images

Funkcje:
- auto-generation z tytułu artykułu
- dynamic typography
- background templates
- style per kategoria

Formaty:
- `1200x630` (Open Graph)
- `16:9` (article hero)
- `1:1` (social)

Wymaganie:
- auto-zapis do biblioteki mediów WordPress.

### 6.9 SEO Automation Engine
Funkcje:
- schema markup automation
- review schema
- FAQ schema
- ranking schema
- breadcrumbs
- table of contents
- related articles
- internal linking automation

### 6.10 Programmatic SEO Engine
Generator masowych landing pages, np.:
- `Jak wybrać [produkt]`
- `Najlepszy [produkt] 2026`
- `Ranking [produkt]`

Wymagania:
- templaty parametryczne
- kontrola jakości publikacji
- harmonogram batch generation

## 7) Multilingual SEO
Języki:
- Polish (`/pl/`)
- English (`/en/`)
- German (`/de/`)
- Spanish (`/es/`)
- French (`/fr/`)

Switcher:
- `Polski | English | Deutsch | Español | Français`

Wymagania SEO:
- hreflang
- locale schema
- mapowanie ekwiwalentów URL między językami

## 8) Struktura homepage
- Hero search
- Latest guides
- Top rankings
- Best reviews
- Best tools
- Sponsored content
- Newsletter

## 9) Struktura theme (frontend)
`generatepress-child-poradnik/`
- `style.css`
- `functions.php`
- `assets/` (`css`, `js`, `images`)
- `template-parts/` (`guide`, `ranking`, `review`)
- `inc/` (`seo`, `ads`, `affiliate`, `dashboard`, `images`)

## 10) Struktura MU-plugins (core)
`wp-content/mu-plugins/`
- `poradnik-platform-loader.php`
- `PORADNIK-PRO-PLATFORM-BLUEPRINT.md`
- `platform-core/`
  - `Core/` (Bootstrap, ModuleRegistry, runtime)
  - `Admin/` (ModuleFlagsPage)
  - `Api/` (REST controllers)
  - `Domain/` (entities, services)
  - `Infrastructure/` (DB, integrations, queues)
  - `Modules/`
    - `Affiliate/`
    - `Rankings/`
    - `Reviews/`
    - `AdsMarketplace/`
    - `Sponsored/`
    - `AdvertiserDashboard/`
    - `AiContent/`
    - `AiImage/`
    - `SeoAutomation/`
    - `ProgrammaticSeo/`

## 11) REST API Contracts (minimum)
- `POST /wp-json/poradnik/v1/affiliate/click`
- `GET /wp-json/poradnik/v1/affiliate/products`
- `POST /wp-json/poradnik/v1/ads/click`
- `POST /wp-json/poradnik/v1/ads/impression`
- `POST /wp-json/poradnik/v1/sponsored/orders`
- `GET /wp-json/poradnik/v1/dashboard/statistics`
- `POST /wp-json/poradnik/v1/ai/content/generate`
- `POST /wp-json/poradnik/v1/ai/image/generate`
- `POST /wp-json/poradnik/v1/seo/programmatic/build`

## 12) Baza danych (minimum)
- `affiliate_products`
- `affiliate_clicks`
- `affiliate_categories`
- `ad_campaigns`
- `ad_slots`
- `ad_clicks`
- `ad_impressions`
- `sponsored_articles`

Każda tabela powinna zawierać:
- `id`, `created_at`, `updated_at`
- indeksy pod raportowanie (`campaign_id`, `slot_id`, `source`, `created_at`)
- zgodność z `dbDelta` (charset/collation WP)

## 13) Performance i skalowanie
- lazy loading obrazów
- image optimization pipeline
- cache compatibility (page + object cache)
- CDN-ready assets
- batch/queue dla operacji masowych (SEO i AI)

## 14) Analytics i KPI
Integracje:
- Google Analytics
- Google Search Console

Śledzenie:
- affiliate clicks
- ad clicks
- CTR

KPI platformowe:
- organic sessions
- revenue per 1000 sessions
- sponsored order volume
- fill rate i eCPM slotów

## 15) Security i compliance
- nonce verification
- role/capability permissions
- input sanitization + output escaping
- audyt zmian statusów kampanii i płatności
- webhook signature verification (Stripe)

## 16) Design system
Styl:
- clean
- modern
- knowledge portal

Kolory bazowe:
- `#ffffff`
- `#111111`
- `#6b4eff`

Układ:
- mobile-first

## 17) Feature flags (już wdrożone)
Źródła konfiguracji modułów:
- opcja: `poradnik_platform_module_flags`
- filtr: `poradnik_platform_module_flags`

Hooki runtime:
- `poradnik_platform_module_loaded_file`
- `poradnik_platform_module_skipped`
- `poradnik_platform_module_missing_bootstrap`

Panel:
- `WP Admin -> Tools -> Poradnik Platform Modules`
- obsługa Save + Reset to Defaults

## 18) Roadmap wdrożenia produkcyjnego
### Faza 1 – Foundation
- pełne CPT + taksonomie + ACF fields
- migration runner dla tabel
- wspólny event logger

### Faza 2 – Monetization Core
- affiliate + ads + sponsored workflow
- Stripe checkout + webhooks
- dashboard podstawowych KPI

### Faza 3 – AI + SEO Automation
- AI content tools
- AI image generator pipeline
- schema + internal linking automation

### Faza 4 – Programmatic Scale
- masowy generator SEO pages
- multilingual rollout
- optymalizacja pod miliony UU

## 19) Definition of Done
Moduł jest „production-ready”, jeśli:
- ma jawny bootstrap i feature flag
- ma walidowane endpointy REST/AJAX
- emituje eventy domenowe
- zapisuje metryki i logi operacyjne
- posiada dokumentację techniczną i checklistę QA

---

Ten dokument jest specyfikacją generatora platformy PORADNIK.PRO w wariancie `mu-plugins-first` i stanowi kontrakt architektoniczny dla dalszej implementacji modułów w środowisku produkcyjnym.
