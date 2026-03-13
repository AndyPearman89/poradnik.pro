# PORADNIK.PRO – SPRINT 2 TASKS (MULTILINGUAL + HARDENING + DASHBOARD.PRO FOUNDATION)

Data: 13 marca 2026  
Cel sprintu: multilingual SEO, security hardening, analytics, performance i fundament Dashboard.PRO (role SaaS).

## Zakres Sprint 2
- Multilingual SEO (PL/EN/DE/ES/FR)
- Security + hardening (nonce, sanityzacja, rate limiting)
- Analytics + Measurement (GA, GSC, eventy)
- Performance + Scale (cache, CDN, queuing)
- QA smoke testy modułów
- Dashboard.PRO Faza 1: User roles + User Dashboard skeleton

## Definicja ukończenia sprintu
- Wszystkie pliki PHP przechodzą `php -l`
- Multilingual routes zwracają poprawne hreflang
- Nonce + capability check na każdym formularzu i endpoincie admin
- GA event tracking działa dla affiliate_click i ad_click
- `GET /wp-json/poradnik/v1/health` zwraca 200 i status `ok`
- Custom roles `poradnik_user` i `poradnik_specialist` są rejestrowane
- User Dashboard dostępny pod `/dashboard/` po zalogowaniu

## Status realizacji
- [ ] T1. Multilingual SEO module
- [ ] T2. Analytics + Measurement
- [ ] T3. Security hardening
- [ ] T4. Performance + Scale
- [ ] T5. QA smoke tests
- [ ] T6. Dashboard.PRO: custom roles
- [ ] T7. Dashboard.PRO: User Dashboard skeleton
- [ ] T8. Dashboard.PRO: database migrations (user tables)

---

## Zadania techniczne

### T1. Multilingual SEO Module
Priorytet: P1

Zakres:
- Utworzyć moduł `Modules/Multilingual/` z `bootstrap.php` i klasą `Module`
- Zarejestrować języki: PL (`/pl/`), EN (`/en/`), DE (`/de/`), ES (`/es/`), FR (`/fr/`)
- Dodać `hreflang` meta tagi w `wp_head` per język
- Dodać `locale` do schema.org JSON-LD
- Dodać language switcher shortcode: `[poradnik_lang_switcher]`
- Mapowanie URL ekwiwalentów między językami przez post meta `_hreflang_{lang}`

Kryteria akceptacji:
- Strony w każdym języku mają poprawne hreflang
- Switcher renderuje linki do wersji językowych
- Brak fatali bez aktywnego pluginu multilingual (np. WPML/Polylang)

### T2. Analytics + Measurement
Priorytet: P1

Zakres:
- Dodać `Domain/Analytics/EventTracker.php`:
  - metody: `trackAffiliateClick`, `trackAdClick`, `trackAdImpression`
  - wyemitowanie event `poradnik_analytics_event` z payloadem
- Dodać Google Analytics 4 data-layer push na events affiliate_click, ad_click, ad_impression
- Dodać hook do integracji z Google Search Console property przez admin settings
- Dodać widok KPI w Admin Dashboard: sesje, CTR, revenue per typ

Kryteria akceptacji:
- Każde kliknięcie afiliacyjne emituje event domenowy
- GA4 gtag event jest wypychany do dataLayer
- Panel KPI wyświetla top 5 produktów afiliacyjnych per CTR

### T3. Security Hardening
Priorytet: P0

Zakres:
- Dodać nonce weryfikację do wszystkich formularzy w Admin pages (audit)
- Dodać rate limiting dla REST endpointów:
  - `POST /affiliate/click` – max 60 req/min per IP
  - `POST /ads/click` – max 60 req/min per IP
  - `POST /ads/impression` – max 120 req/min per IP
- Dodać sanitizację wejścia do wszystkich `$_POST` w Admin pages (audit + fix)
- Dodać `escape_output` audit dla wszystkich `echo` w Admin pages
- Dodać Stripe webhook signature verification (jeśli brakuje)

Kryteria akceptacji:
- `grep -r 'check_admin_referer\|verify_nonce' --include="*.php"` zwraca wynik dla każdej formy admin
- Endpointy click/impression mają limit i zwracają 429 po przekroczeniu
- `grep -r 'wp_unslash\|sanitize_' Admin/ --include="*.php"` obejmuje wszystkie pola POST

### T4. Performance + Scale
Priorytet: P1

Zakres:
- Dodać `Infrastructure/Cache/ObjectCacheHelper.php`:
  - metody: `get`, `set`, `delete` z prefixem `poradnik_`
  - wrapper na `wp_cache_*`
- Zastosować cache w `Domain/Dashboard/StatsService.php` (TTL 5 min)
- Zastosować cache w `Domain/Ranking/Builder.php` (TTL 10 min)
- Dodać lazy loading atrybutu `loading="lazy"` do wszystkich `<img>` renderowanych przez moduły
- Dodać `Infrastructure/Queue/AsyncJobRunner.php`:
  - `schedule(string $hook, array $args, int $delaySeconds)` wrapper na `wp_schedule_single_event`
  - Użyć w `AiContent` i `ProgrammaticSeo` dla batch operations

Kryteria akceptacji:
- Stats i Ranking mają cache miss/hit log przez EventLogger
- Lazy loading jest na wszystkich img w shortcodach affiliate i ads
- AsyncJobRunner jest używany w co najmniej 1 module (ProgrammaticSeo)

### T5. QA Smoke Tests
Priorytet: P1

Zakres:
- Dodać `php -l` check dla wszystkich plików `platform-core/**/*.php`
- Dodać listę smoke checklist dla wszystkich 11 modułów:
  - Czy bootstrap.php ładuje się bez fatal?
  - Czy `Module::init()` rejestruje hooki?
  - Czy endpointy REST zwracają właściwe kody HTTP?
- Dodać QA Runner: skrypt `tools/qa-lint.sh` do `php -l` wszystkich plików platformy

Kryteria akceptacji:
- `bash tools/qa-lint.sh` zwraca 0 exit code
- Lista smoke tests jest opisana w PORADNIK-PRO-IMPLEMENTATION-CHECKLIST.md (sekcja 18)

### T6. Dashboard.PRO: Custom Roles
Priorytet: P1

Zakres:
- Dodać `Core/RoleManager.php`:
  - `registerRoles()`: tworzy role `poradnik_user`, `poradnik_specialist`, `poradnik_advertiser` (jeśli nie istnieje)
  - `getCapabilities(string $role)`: zwraca mapę capabilities
- Wywołać `RoleManager::registerRoles()` w `Core/Bootstrap::init()` przez `register_activation_hook` lub `init` hook
- Dodać capabilities: `manage_platform`, `manage_own_specialist_profile`, `publish_specialist_content`, `view_own_earnings`

Kryteria akceptacji:
- Role są widoczne w `WP Admin -> Users -> Roles`
- Przypisanie roli `poradnik_specialist` daje dostęp do Specialist Dashboard
- Role nie są nadpisywane przy każdym ładowaniu strony (idempotentne)

### T7. Dashboard.PRO: User Dashboard Skeleton
Priorytet: P2

Zakres:
- Dodać moduł `Modules/UserDashboard/` z `bootstrap.php` i `Module.php`
- Zarejestrować page template `dashboard` (custom page template lub virtual endpoint)
- Dodać redirect: niezalogowany użytkownik → `wp_login_url`
- Dodać widok: Overview (powitanie + linki do sekcji)
- Dodać REST endpoint stub: `GET /wp-json/poradnik/v1/user/favorites` (zwraca puste `[]`)
- Dodać REST endpoint stub: `POST /wp-json/poradnik/v1/user/favorites` (przyjmuje `post_id`, zapisuje)

Kryteria akceptacji:
- Zalogowany użytkownik widzi `/dashboard/` z powitaniem i nazwą
- Niezalogowany jest redirectowany do logowania
- Endpoint favorites zwraca `[]` bez błędu 500

### T8. Dashboard.PRO: Database Migrations (User Tables)
Priorytet: P1

Zakres:
- Rozszerzyć `Infrastructure/Database/Migrator.php` do wersji `1.4.0`
- Dodać tabele:
  - `{prefix}poradnik_user_favorites` (id, user_id, post_id, created_at)
  - `{prefix}poradnik_user_history` (id, user_id, post_id, viewed_at)
  - `{prefix}poradnik_user_subscriptions` (id, user_id, topic_slug, created_at)
- Dodać indeksy: `user_id`, `post_id`, `created_at`

Kryteria akceptacji:
- Migracja jest idempotentna (działa wielokrotnie bez błędów)
- Tabele są tworzone przy aktywacji lub upgradie wersji DB
- `SHOW TABLES LIKE '%poradnik_user%'` zwraca 3 tabele

---

## Plan wykonania (kolejność)

1. T6 Custom Roles (fundament RBAC)
2. T8 DB migrations user tables
3. T3 Security hardening (P0)
4. T4 Performance helpers (cache + queue)
5. T1 Multilingual module
6. T2 Analytics EventTracker
7. T7 User Dashboard skeleton
8. T5 QA lint + smoke tests

---

## Ryzyka sprintu

- Konflikty z istniejącymi rolami WordPress przy `add_role`
- Rate limiting wymaga persistentnego storage (Redis lub transients)
- Multilingual bez WPML/Polylang wymaga własnej logiki URL mapowania
- User Dashboard template może kolidować z istniejącymi page templates

---

## Testy końcowe sprintu

- `bash tools/qa-lint.sh` → 0 errors
- `GET /wp-json/poradnik/v1/health` → `{"status":"ok"}`
- `GET /wp-json/poradnik/v1/user/favorites` (zalogowany) → `[]`
- `POST /wp-json/poradnik/v1/user/favorites` (zalogowany, post_id=1) → `{"saved":true}`
- User Dashboard pod `/dashboard/` po zalogowaniu → widoczne
- Nowe tabele w DB → widoczne przez `SHOW TABLES`
- php -l na wszystkich plikach → brak błędów składni

---

## Deliverables

- Moduł Multilingual + hreflang
- Analytics EventTracker + GA4 dataLayer
- Security hardened admin + rate limiting
- Cache layer + AsyncJobRunner
- RoleManager (custom roles)
- User Dashboard skeleton
- DB migrator v1.4.0 (user tables)
- qa-lint.sh skrypt

---

Po Sprint 2 platforma ma pełny fundament SaaS z rolami użytkowników, bezpiecznym zapleczem i gotową infrastrukturą pod Specialist Dashboard i pełne Dashboard.PRO w Sprint 3.
