# PORADNIK.PRO – SPRINT 1 TASKS (FOUNDATION)

Data: 12 marca 2026  
Cel sprintu: uruchomić stabilny fundament platformy MU (runtime + content model + baza danych).

## Zakres Sprint 1
- Foundation runtime w `mu-plugins/platform-core`
- Rejestracja CPT + taksonomii
- Warstwa migracji DB (dbDelta)
- Minimalne endpointy health + tracking testowy
- Bazowe bezpieczeństwo i observability

## Definicja ukończenia sprintu
- Wszystkie pliki PHP przechodzą `php -l`
- CPT i taksonomie są widoczne w WP Admin
- Tabele DB tworzą się idempotentnie
- Endpoint health zwraca 200 i status `ok`
- Logika działa pod feature flags i nie łamie obecnego runtime

## Status realizacji (zapis)
- [x] T1. Core runtime hardening
- [x] T2. Rejestracja Content Types
- [x] T3. Rejestracja Taxonomii
- [x] T4. ACF integration skeleton
- [x] T5. DB migration runner
- [x] T6. Minimalne REST endpointy
- [x] T7. Security baseline
- [x] T8. Observability baseline

### Dodatkowo wykonane ponad Sprint 1
- [x] Moduł Affiliate: shortcodes + cloaked redirect + tracking + panel CRUD produktów
- [x] Moduł Ads Marketplace: slot rendering + redirect + click/impression tracking + panel kampanii
- [x] Moduł Sponsored: workflow submit/review/paid/publish + panel zamówień + REST `POST /sponsored/orders`
- [x] Moduł Advertiser Dashboard: zakładki Overview/Campaigns/Statistics/Payments + REST dashboard endpoints
- [x] Moduły AI: AI Article Assistant + AI Image Generator + REST `POST /ai/content/generate` i `POST /ai/image/generate`
- [x] Moduł SEO Automation: meta description, schema JSON-LD, TOC i related links automation
- [x] Moduł Programmatic SEO: panel generatora i REST `POST /seo/programmatic/build`
- [x] Moduł Rankings: builder rankingu + scoring + frontend injection + REST `GET/POST /rankings`
- [x] Moduł Reviews: review box + metryki opinii + frontend injection + REST `GET/POST /reviews`

## Zadania techniczne

### T1. Core runtime hardening
Priorytet: P0

Zakres:
- Dodać `Core/Runtime.php` z inicjalizacją środowiska platformy
- Dodać `Core/EventLogger.php` (wrapper na `do_action` + log kanału)
- Dodać `Core/Capabilities.php` (wspólne sprawdzanie uprawnień)
- Podpiąć `Runtime::init()` w `Core/Bootstrap.php`

Kryteria akceptacji:
- Nie ma błędów przy ładowaniu `mu-plugins`
- Event `poradnik_platform_bootstrapped` jest emitowany raz
- Log helper działa bez fatal errors

### T2. Rejestracja Content Types
Priorytet: P0

Zakres:
- Utworzyć moduł `Modules/ContentModel/` z `bootstrap.php` i klasą `Module`
- Zarejestrować CPT: `guide`, `ranking`, `review`, `comparison`, `news`, `tool`, `sponsored`
- Dodać spójne labels, `show_in_rest`, rewrite slugi PL
- Dodać wsparcie: title, editor, excerpt, thumbnail, author, revisions

Kryteria akceptacji:
- CPT widoczne w admin i API
- URL rewrite działa po flush permalinks
- Edycja wpisu działa bez notice/errors

### T3. Rejestracja Taxonomii
Priorytet: P0

Zakres:
- Dodać współdzielone taxonomie: `topic`, `intent`, `stage`, `industry`
- Podpiąć do wszystkich 7 CPT
- Dodać `show_in_rest=true` dla kompatybilności z blokami i API

Kryteria akceptacji:
- Termy można tworzyć i przypisywać
- Filtrowanie przez REST działa

### T4. ACF integration skeleton
Priorytet: P1

Zakres:
- Utworzyć `Domain/Content/FieldGroups.php`
- Programatycznie zarejestrować grupy pól (jeśli aktywne ACF):
  - reading_time
  - toc_enabled
  - faq_items
  - related_articles
- Dodać fallback (brak ACF nie powoduje fatal error)

Kryteria akceptacji:
- Pola pojawiają się na ekranach CPT
- Wyłączenie ACF nie psuje działania platformy

### T5. DB migration runner
Priorytet: P0

Zakres:
- Utworzyć `Infrastructure/Database/Migrator.php`
- Dodać wersję schematu opcji, np. `poradnik_platform_db_version`
- Wykonać `dbDelta` dla tabel:
  - affiliate_products
  - affiliate_clicks
  - affiliate_categories
  - ad_campaigns
  - ad_slots
  - ad_clicks
  - ad_impressions
  - sponsored_articles
- Dodać indeksy pod `created_at`, `campaign_id`, `slot_id`, `source`

Kryteria akceptacji:
- Migracja jest idempotentna
- Tabele mają poprawny charset/collation
- Upgrade path działa po zwiększeniu wersji

### T6. Minimalne REST endpointy
Priorytet: P1

Zakres:
- Utworzyć namespace `Api/Controllers/`
- Dodać `HealthController`:
  - `GET /wp-json/poradnik/v1/health`
- Dodać testowy tracking controller:
  - `POST /wp-json/poradnik/v1/affiliate/click`
  - payload: product_id, source, post_id
- Dodać walidację i sanitizację danych wejściowych

Kryteria akceptacji:
- Health endpoint zwraca `{"status":"ok"}`
- Endpoint click zapisuje rekord do `affiliate_clicks`
- Permission callbacks są jawnie zdefiniowane

### T7. Security baseline
Priorytet: P0

Zakres:
- Dodać helper nonce do formularzy admin
- Dodać centralny `Permission::canManagePlatform()`
- Ujednolicić sanitizację (`sanitize_text_field`, `absint`, `esc_url_raw`)
- Ujednolicić escaping na renderze admin (`esc_html`, `esc_attr`)

Kryteria akceptacji:
- Każda nowa akcja admin ma nonce + capability check
- Brak bezpośredniego zapisu nieprzefiltrowanych danych

### T8. Observability baseline
Priorytet: P1

Zakres:
- Dodać eventy domenowe dla:
  - content model registered
  - db migrated
  - rest route registered
- Dodać prosty debug log toggle przez filtr

Kryteria akceptacji:
- Eventy są emitowane i możliwe do nasłuchu
- Debug log można włączyć/wyłączyć bez zmian kodu

## Plan wykonania (kolejność)
1. T1 Core runtime hardening
2. T2 + T3 Content Model
3. T5 DB migration runner
4. T6 REST minimal
5. T7 Security baseline
6. T8 Observability
7. T4 ACF integration skeleton

## Ryzyka sprintu
- Konflikty slugów CPT z istniejącymi treściami
- Brak flush permalinks po wdrożeniu CPT
- Niewłaściwe indeksy w tabelach przy dużym wolumenie
- Brak ACF Pro w środowisku testowym

## Testy końcowe sprintu
- `php -l` dla wszystkich nowych plików
- Smoke test WP Admin dla 7 CPT
- Smoke test tworzenia termów taksonomii
- Test endpointu `GET /poradnik/v1/health`
- Test endpointu `POST /poradnik/v1/affiliate/click`
- Weryfikacja utworzenia tabel SQL

## Deliverables
- Nowe klasy runtime/core/content/db/rest/security
- Działające CPT + taxonomie
- Działające migracje DB
- Minimalne REST API gotowe pod Sprint 2

## Uwaga operacyjna
- Po wdrożeniu rewrite rules wymagane jednorazowe odświeżenie permalinków w WP Admin.

---

## Dziennik wykonania

| Data | Obszar | Test / Zadanie | Wynik | Owner | Uwagi |
|---|---|---|---|---|---|
| 2026-03-13 | Runtime | Core bootstrap + eventy | PASS | platform-team | `poradnik_platform_bootstrapped` emitowany |
| 2026-03-13 | Content Model | Rejestracja CPT + taxonomie | PASS | platform-team | Widoczne w admin i REST |
| 2026-03-13 | Database | Migracje `dbDelta` i indeksy | PASS | platform-team | Migracje idempotentne |
| 2026-03-13 | REST | Health + tracking endpointy | PASS | platform-team | Walidacja payloadu aktywna |
| 2026-03-13 | Security | Nonce + capability + escaping | PASS | platform-team | Bez krytycznych regresji |
| 2026-03-13 | QA | Lint `php -l` | PASS | platform-team | Brak błędów składni |

### Szablon nowego wpisu
`| YYYY-MM-DD | Obszar | Test / Zadanie | PASS/FAIL | Owner | Krótka notatka |`

---

Po Sprint 1 platforma ma stabilny fundament techniczny pod wdrożenie modułów monetyzacyjnych (Affiliate/Ads/Sponsored) w Sprint 2.
