# PORADNIK.PRO  IMPLEMENTATION CHECKLIST (MU-PLUGINS)

Data: 12 marca 2026  
Status: Execution Checklist v1

## 0) Foundation / Core Runtime
- [x] Potwierdzic dzialanie loadera MU (`poradnik-platform-loader.php`)
- [x] Potwierdzic autoload + bootstrap (`Core/Bootstrap.php`)
- [x] Potwierdzic dynamiczne wykrywanie modulow (`Core/ModuleRegistry.php`)
- [x] Potwierdzic feature flags (opcja + filtr)
- [x] Potwierdzic panel `Tools -> Poradnik Platform Modules`
- [x] Dodac centralny logger zdarzen platformy
- [x] Dodac wspolny helper uprawnien/capabilities

## 1) Content Model (CPT + Taxonomies + ACF)
- [x] Zarejestrowac CPT: `guide`
- [x] Zarejestrowac CPT: `ranking`
- [x] Zarejestrowac CPT: `review`
- [x] Zarejestrowac CPT: `comparison`
- [x] Zarejestrowac CPT: `news`
- [x] Zarejestrowac CPT: `tool`
- [x] Zarejestrowac CPT: `sponsored`
- [x] Zarejestrowac taksonomie: `topic`, `intent`, `stage`, `industry`
- [x] Dodac ACF fields: featured image, reading time, TOC, FAQ, related
- [x] Dodac relacje miedzy artykulami (related articles)
- [x] Dodac automatyczne obliczanie reading time

## 2) Database Layer (Infrastructure)
- [x] Dodac migration runner (`dbDelta`) dla tabel platformy
- [x] Utworzyc `affiliate_products`
- [x] Utworzyc `affiliate_clicks`
- [x] Utworzyc `affiliate_categories`
- [x] Utworzyc `ad_campaigns`
- [x] Utworzyc `ad_slots`
- [x] Utworzyc `ad_clicks`
- [x] Utworzyc `ad_impressions`
- [x] Utworzyc `sponsored_articles`
- [x] Dodac indeksy pod raportowanie (campaign/slot/source/created_at)
- [x] Dodac wersjonowanie schematu DB (option key + upgrade path)

## 3) Affiliate Engine
- [x] Model produktu afiliacyjnego + CRUD admin
- [x] Link cloaking + endpoint redirect tracking
- [x] Shortcode `[affiliate_product]`
- [x] Shortcode `[comparison_table]`
- [x] Shortcode `[top_product]`
- [x] Tracking klikow (source, referrer, post_id)
- [x] KPI: CTR i top-performing products

## 4) Ranking Engine
- [x] Builder rankingow (manual + dynamic scoring)
- [x] Product cards z pros/cons/spec
- [x] Filtry i sortowanie frontend
- [x] Wyroznienie pozycje (monetyzowane)
- [x] Integracja z Affiliate CTA
- [x] Schema dla list rankingowych

## 5) Review Engine
- [x] Model recenzji: rating, verdict, pros/cons, specs
- [x] UI edycji recenzji (ACF + metabox)
- [x] Schema `Review`, `Product`, `Rating`
- [x] Bloki: FAQ i related reviews
- [x] Integracja z porownianiami

## 6) Ad Marketplace
- [x] Definicja slotow (Hero/Sidebar/Inline/Footer)
- [x] Campaign manager (budzet, data start/koniec, status)
- [x] Rotacja reklam i pacing
- [x] Tracking impressions/clicks
- [x] Raport KPI per slot i per campaign
- [x] Integracja z AdSense fallback

## 7) Sponsored Articles System
- [x] Formularz zgloszenia artykulu sponsorowanego
- [x] Workflow: submit -> review -> payment -> publish
- [x] Pakiety: Basic / Featured / Homepage
- [x] Oznaczenie sponsored badge
- [x] Automatyczne rel="nofollow sponsored" dla linkow sponsorowanych
- [x] Dashboard statusow zamowien sponsorowanych

## 8) Advertiser SaaS Dashboard
- [x] Widok Overview
- [x] Widok Campaigns
- [x] Widok Statistics
- [x] Widok Payments
- [x] RBAC: oddzielenie panelu reklamodawcy od WP admin
- [x] API statystyk: impressions/clicks/CTR
- [x] Historia faktur i statusow platnosci

## 9) Stripe Payments
- [x] Konfiguracja checkout dla kampanii i sponsored
- [x] Webhook receiver + signature verification
- [x] Obsluga statusow payment intent
- [x] Retry i idempotencja webhookow
- [x] Reconciliation job (platnosc vs status kampanii)

## 10) SEO Automation Engine
- [x] Meta title/description automation
- [x] Canonical + robots control
- [x] Breadcrumbs
- [x] TOC auto block
- [x] Internal linking automation
- [x] Related articles automation
- [x] Schema: FAQ / Review / Ranking / Article

## 11) Programmatic SEO Engine
- [x] Parametryczne templaty artykulow
- [x] Batch generator: Jak wybrac [produkt]
- [x] Batch generator: Najlepszy [produkt] 2026
- [x] Batch generator: Ranking [produkt]
- [x] QA guardrails przed publikacja
- [x] Harmonogram i limity dzienne publikacji

## 12) AI Content Generator
- [ ] Headline generator
- [ ] Outline generator
- [ ] FAQ generator
- [ ] Meta description generator
- [ ] Ranking copy generator
- [ ] Panel AI Article Assistant w admin
- [ ] Guardrails: jakosc, dlugosc, banned claims

## 13) AI Image Generator
- [ ] Generator obrazow z tytulu artykulu
- [ ] Szablony typografii i tla per kategoria
- [ ] Format 1200x630 (OG)
- [ ] Format 16:9 (hero)
- [ ] Format 1:1 (social)
- [ ] Auto-upload do Media Library
- [ ] Auto-podpiecie jako featured image / OG image

## 14) Multilingual SEO
- [ ] Konfiguracja jezykow: PL/EN/DE/ES/FR
- [ ] URL strategy: /pl/, /en/, /de/, /es/, /fr/
- [ ] Language switcher frontend
- [ ] Hreflang mapowanie miedzy wersjami
- [ ] Lokalizacja schema i metadanych

## 15) Analytics + Measurement
- [ ] Integracja Google Analytics
- [ ] Integracja Google Search Console
- [ ] Eventy: affiliate_click, ad_click, ad_impression
- [ ] Dashboard KPI: sessions/CTR/revenue per type
- [ ] Raporty tygodniowe i miesieczne

## 16) Security + Hardening
- [ ] Nonce verification dla formularzy i AJAX
- [ ] Sanitizacja wejscia + escaping wyjscia
- [ ] Capability checks dla kazdej akcji admin
- [ ] Ochrona endpointow REST (permission callbacks)
- [ ] Audyt logow bezpieczenstwa

## 17) Performance + Scale
- [ ] Lazy loading obrazow
- [ ] Kompatybilnosc z cache (page/object)
- [ ] CDN readiness
- [ ] Optymalizacja zapytan DB i indeksow
- [ ] Kolejkowanie zadan ciezkich (AI/Programmatic)

## 18) QA / Release Readiness
- [ ] Testy smoke dla wszystkich modulow
- [ ] Lint PHP dla mu-plugins/platform-core/**
- [ ] Testy endpointow REST (auth + validation)
- [ ] Test scenariusza E2E: sponsored workflow
- [ ] Test scenariusza E2E: ad campaign workflow
- [ ] Test scenariusza E2E: affiliate tracking
- [ ] Dokumentacja runbook + rollback plan

## 19) Go-Live Checklist
- [ ] Backup pelny przed deploy
- [ ] Wdrozenie migracji DB
- [ ] Weryfikacja webhooks Stripe
- [ ] Weryfikacja tagow Analytics/GSC
- [ ] Weryfikacja SEO (schema + sitemap + hreflang)
- [ ] Monitoring 24h po deploy
- [ ] Monitoring 7 dni (errors, revenue, CTR)

---

## Sugerowana kolejnosc sprintow
1. Foundation + Content Model + DB OK
2. Affiliate + Ranking + Review OK
3. Ads + Sponsored + Stripe OK
4. SEO Automation + Programmatic SEO OK
5. AI Content + AI Image <- nastepny
6. Multilingual + final hardening + go-live