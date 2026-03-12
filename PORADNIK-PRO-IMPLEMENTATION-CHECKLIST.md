# PORADNIK.PRO – IMPLEMENTATION CHECKLIST (MU-PLUGINS)

Data: 12 marca 2026  
Status: Execution Checklist v1

## 0) Foundation / Core Runtime
- [ ] Potwierdzić działanie loadera MU (`poradnik-platform-loader.php`)
- [ ] Potwierdzić autoload + bootstrap (`Core/Bootstrap.php`)
- [ ] Potwierdzić dynamiczne wykrywanie modułów (`Core/ModuleRegistry.php`)
- [ ] Potwierdzić feature flags (opcja + filtr)
- [ ] Potwierdzić panel `Tools -> Poradnik Platform Modules`
- [ ] Dodać centralny logger zdarzeń platformy
- [ ] Dodać wspólny helper uprawnień/capabilities

## 1) Content Model (CPT + Taxonomies + ACF)
- [ ] Zarejestrować CPT: `guide`
- [ ] Zarejestrować CPT: `ranking`
- [ ] Zarejestrować CPT: `review`
- [ ] Zarejestrować CPT: `comparison`
- [ ] Zarejestrować CPT: `news`
- [ ] Zarejestrować CPT: `tool`
- [ ] Zarejestrować CPT: `sponsored`
- [ ] Zarejestrować taksonomie: `topic`, `intent`, `stage`, `industry`
- [ ] Dodać ACF fields: featured image, reading time, TOC, FAQ, related
- [ ] Dodać relacje między artykułami (related articles)
- [ ] Dodać automatyczne obliczanie reading time

## 2) Database Layer (Infrastructure)
- [ ] Dodać migration runner (`dbDelta`) dla tabel platformy
- [ ] Utworzyć `affiliate_products`
- [ ] Utworzyć `affiliate_clicks`
- [ ] Utworzyć `affiliate_categories`
- [ ] Utworzyć `ad_campaigns`
- [ ] Utworzyć `ad_slots`
- [ ] Utworzyć `ad_clicks`
- [ ] Utworzyć `ad_impressions`
- [ ] Utworzyć `sponsored_articles`
- [ ] Dodać indeksy pod raportowanie (campaign/slot/source/created_at)
- [ ] Dodać wersjonowanie schematu DB (option key + upgrade path)

## 3) Affiliate Engine
- [ ] Model produktu afiliacyjnego + CRUD admin
- [ ] Link cloaking + endpoint redirect tracking
- [ ] Shortcode `[affiliate_product]`
- [ ] Shortcode `[comparison_table]`
- [ ] Shortcode `[top_product]`
- [ ] Tracking klików (source, referrer, post_id)
- [ ] KPI: CTR i top-performing products

## 4) Ranking Engine
- [ ] Builder rankingów (manual + dynamic scoring)
- [ ] Product cards z pros/cons/spec
- [ ] Filtry i sortowanie frontend
- [ ] Wyróżnione pozycje (monetyzowane)
- [ ] Integracja z Affiliate CTA
- [ ] Schema dla list rankingowych

## 5) Review Engine
- [ ] Model recenzji: rating, verdict, pros/cons, specs
- [ ] UI edycji recenzji (ACF + metabox)
- [ ] Schema `Review`, `Product`, `Rating`
- [ ] Bloki: FAQ i related reviews
- [ ] Integracja z porównaniami

## 6) Ad Marketplace
- [ ] Definicja slotów (Hero/Sidebar/Inline/Footer)
- [ ] Campaign manager (budżet, data start/koniec, status)
- [ ] Rotacja reklam i pacing
- [ ] Tracking impressions/clicks
- [ ] Raport KPI per slot i per campaign
- [ ] Integracja z AdSense fallback

## 7) Sponsored Articles System
- [ ] Formularz zgłoszenia artykułu sponsorowanego
- [ ] Workflow: submit -> review -> payment -> publish
- [ ] Pakiety: Basic / Featured / Homepage
- [ ] Oznaczenie sponsored badge
- [ ] Automatyczne `rel="nofollow sponsored"` dla linków sponsorowanych
- [ ] Dashboard statusów zamówień sponsorowanych

## 8) Advertiser SaaS Dashboard
- [ ] Widok Overview
- [ ] Widok Campaigns
- [ ] Widok Statistics
- [ ] Widok Payments
- [ ] RBAC: oddzielenie panelu reklamodawcy od WP admin
- [ ] API statystyk: impressions/clicks/CTR
- [ ] Historia faktur i statusów płatności

## 9) Stripe Payments
- [ ] Konfiguracja checkout dla kampanii i sponsored
- [ ] Webhook receiver + signature verification
- [ ] Obsługa statusów payment intent
- [ ] Retry i idempotencja webhooków
- [ ] Reconciliation job (płatność vs status kampanii)

## 10) SEO Automation Engine
- [ ] Meta title/description automation
- [ ] Canonical + robots control
- [ ] Breadcrumbs
- [ ] TOC auto block
- [ ] Internal linking automation
- [ ] Related articles automation
- [ ] Schema: FAQ / Review / Ranking / Article

## 11) Programmatic SEO Engine
- [ ] Parametryczne templaty artykułów
- [ ] Batch generator: „Jak wybrać [produkt]”
- [ ] Batch generator: „Najlepszy [produkt] 2026”
- [ ] Batch generator: „Ranking [produkt]”
- [ ] QA guardrails przed publikacją
- [ ] Harmonogram i limity dzienne publikacji

## 12) AI Content Generator
- [ ] Headline generator
- [ ] Outline generator
- [ ] FAQ generator
- [ ] Meta description generator
- [ ] Ranking copy generator
- [ ] Panel „AI Article Assistant” w admin
- [ ] Guardrails: jakość, długość, banned claims

## 13) AI Image Generator
- [ ] Generator obrazów z tytułu artykułu
- [ ] Szablony typografii i tła per kategoria
- [ ] Format `1200x630` (OG)
- [ ] Format `16:9` (hero)
- [ ] Format `1:1` (social)
- [ ] Auto-upload do Media Library
- [ ] Auto-podpięcie jako featured image / OG image

## 14) Multilingual SEO
- [ ] Konfiguracja języków: PL/EN/DE/ES/FR
- [ ] URL strategy: `/pl/`, `/en/`, `/de/`, `/es/`, `/fr/`
- [ ] Language switcher frontend
- [ ] Hreflang mapowanie między wersjami
- [ ] Lokalizacja schema i metadanych

## 15) Analytics + Measurement
- [ ] Integracja Google Analytics
- [ ] Integracja Google Search Console
- [ ] Eventy: affiliate_click, ad_click, ad_impression
- [ ] Dashboard KPI: sessions/CTR/revenue per type
- [ ] Raporty tygodniowe i miesięczne

## 16) Security + Hardening
- [ ] Nonce verification dla formularzy i AJAX
- [ ] Sanitizacja wejścia + escaping wyjścia
- [ ] Capability checks dla każdej akcji admin
- [ ] Ochrona endpointów REST (permission callbacks)
- [ ] Audyt logów bezpieczeństwa

## 17) Performance + Scale
- [ ] Lazy loading obrazów
- [ ] Kompatybilność z cache (page/object)
- [ ] CDN readiness
- [ ] Optymalizacja zapytań DB i indeksów
- [ ] Kolejkowanie zadań ciężkich (AI/Programmatic)

## 18) QA / Release Readiness
- [ ] Testy smoke dla wszystkich modułów
- [ ] Lint PHP dla `mu-plugins/platform-core/**`
- [ ] Testy endpointów REST (auth + validation)
- [ ] Test scenariusza E2E: sponsored workflow
- [ ] Test scenariusza E2E: ad campaign workflow
- [ ] Test scenariusza E2E: affiliate tracking
- [ ] Dokumentacja runbook + rollback plan

## 19) Go-Live Checklist
- [ ] Backup pełny przed deploy
- [ ] Wdrożenie migracji DB
- [ ] Weryfikacja webhooks Stripe
- [ ] Weryfikacja tagów Analytics/GSC
- [ ] Weryfikacja SEO (schema + sitemap + hreflang)
- [ ] Monitoring 24h po deploy
- [ ] Monitoring 7 dni (errors, revenue, CTR)

---

## Sugerowana kolejność sprintów
1. Foundation + Content Model + DB
2. Affiliate + Ranking + Review
3. Ads + Sponsored + Stripe
4. SEO Automation + Programmatic SEO
5. AI Content + AI Image
6. Multilingual + final hardening + go-live
