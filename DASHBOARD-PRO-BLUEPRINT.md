# DASHBOARD.PRO – SAAS PLATFORM BLUEPRINT

Data: 13 marca 2026  
Status: Blueprint v1 (Multi-Role SaaS Extension)

---

## 1) Cel platformy

DASHBOARD.PRO to rozszerzenie platformy PORADNIK.PRO o pełny system SaaS z wielorolowym zapleczem użytkownika. Platforma dostarcza dedykowane dashboardy dla czterech ról:

| Rola | Panel | Przeznaczenie |
|------|-------|---------------|
| **Admin** | Admin Dashboard | Zarządzanie platformą, moderacja, KPI globalne |
| **User** | User Dashboard | Dostęp do ulubionych, historii, subskrypcji |
| **Specialist** | Specialist Dashboard | Zarządzanie profilem eksperta, publikacjami, ofertami |
| **Advertiser** | Advertiser Dashboard | Kampanie reklamowe, statystyki, płatności (istniejący) |

Wzorzec produktowy: HubSpot / Semrush / Ahrefs / Notion – wielorolowy SaaS z czystym UX per rola.

---

## 2) Architektura ról i RBAC

### Role WordPress (custom capabilities)
- `poradnik_admin` – pełny dostęp do platformy
- `poradnik_specialist` – zarządzanie własnym profilem i treściami
- `poradnik_advertiser` – zarządzanie kampaniami i płatnościami (istniejąca)
- `poradnik_user` – dostęp do funkcji frontendowych

### Capability matrix

| Capability | Admin | Specialist | Advertiser | User |
|-----------|-------|-----------|-----------|------|
| manage_platform | ✅ | ❌ | ❌ | ❌ |
| manage_own_profile | ✅ | ✅ | ✅ | ✅ |
| publish_specialist_content | ✅ | ✅ | ❌ | ❌ |
| manage_campaigns | ✅ | ❌ | ✅ | ❌ |
| view_own_stats | ✅ | ✅ | ✅ | ✅ |
| access_user_dashboard | ✅ | ✅ | ✅ | ✅ |

---

## 3) Moduły dashboardów

### 3.1 Admin Dashboard (rozszerzenie istniejącego WP Admin)

Widoki:
- Platform Overview (KPI globalne: sesje, revenue, fill rate, CTR)
- Users Management (lista użytkowników, role, statusy)
- Content Moderation (kolejka review, spam, quality score)
- Revenue Reports (przychody per kanał: affiliate, ads, sponsored, subscriptions)
- System Health (moduły, API status, błędy, kolejki)

Metryki:
- Total sessions / organic share
- Revenue per 1000 sessions (RPM)
- Active campaigns count / fill rate
- Sponsored orders pipeline

### 3.2 User Dashboard (frontend SaaS)

URL: `/dashboard/` lub `/moje-konto/`

Widoki:
- Overview (powitanie, skróty, ostatnia aktywność)
- Ulubione (zapisane poradniki, rankingi, recenzje)
- Historia (ostatnio przeglądane)
- Subskrypcje (newsletter, powiadomienia tematyczne)
- Ustawienia (profil, hasło, preferencje komunikacji)

Funkcje:
- Zapis artykułów do ulubionych (AJAX, niezalogowany → prompt)
- Historia przeglądania (opcjonalna, opt-in)
- Newsletter opt-in per temat
- Profil publiczny (opcjonalny – do Specialist onboarding)

REST API:
- `GET /wp-json/poradnik/v1/user/favorites`
- `POST /wp-json/poradnik/v1/user/favorites`
- `DELETE /wp-json/poradnik/v1/user/favorites/{id}`
- `GET /wp-json/poradnik/v1/user/history`
- `GET /wp-json/poradnik/v1/user/subscriptions`
- `POST /wp-json/poradnik/v1/user/subscriptions`

### 3.3 Specialist Dashboard (frontend SaaS)

URL: `/dashboard/specialist/`

Wymagana rola: `poradnik_specialist`

Widoki:
- Overview (profil publiczny, statystyki widoczności)
- Moje treści (lista artykułów, statusy, edycja)
- Profil eksperta (bio, specjalizacje, linki, zdjęcie)
- Oferty usług (opcjonalne: listing usług / konsultacji)
- Statystyki (odsłony, CTR, komentarze per artykuł)
- Wypłaty (prowizje za treści, wypłaty affiliate)

Funkcje:
- Formularz zgłoszenia artykułu eksperta
- Workflow: draft → review (editorial) → publish
- Profil publiczny na stronie autora (`/ekspert/{slug}/`)
- Schema `Person` + `author` markup
- Statystyki per artykuł (odsłony z GSC API lub wewnętrzne)

REST API:
- `GET /wp-json/poradnik/v1/specialist/profile`
- `PUT /wp-json/poradnik/v1/specialist/profile`
- `GET /wp-json/poradnik/v1/specialist/posts`
- `POST /wp-json/poradnik/v1/specialist/posts`
- `GET /wp-json/poradnik/v1/specialist/stats`
- `GET /wp-json/poradnik/v1/specialist/earnings`

### 3.4 Advertiser Dashboard (istniejący – rozszerzenie)

Istniejące widoki: Overview, Campaigns, Statistics, Payments  
Nowe widoki:
- Invoices (historia faktur PDF)
- Support (ticket system lub chat)
- Onboarding wizard (pierwsze kroki dla nowego reklamodawcy)

---

## 4) Techniczne aspekty SaaS

### Rejestracja i onboarding

Flow dla nowego użytkownika:
1. Rejestracja WordPress (standard lub custom form)
2. Przypisanie roli domyślnej: `poradnik_user`
3. Email powitalny + weryfikacja
4. Opcjonalny upgrade: `poradnik_specialist` (formularz zgłoszenia) lub `poradnik_advertiser` (onboarding wizard)

### Sesje i autentykacja

- Standardowe sesje WordPress (wp_login)
- Opcjonalnie: JWT dla headless frontend (z pluginem lub custom)
- 2FA: rekomendowane dla ról admin i specialist

### Subskrypcje i płatności (User SaaS)

Plany (opcjonalne, do decyzji biznesowej):
- **Free** – dostęp do wszystkich poradników, ulubione, 1 newsletter topic
- **Pro** – wczesny dostęp, zaawansowane filtry, nieograniczone tematy newslettera
- **Expert** – Pro + dostęp do raportu SEO per artykuł, indywidualne alerty

Integracja: Stripe Subscriptions (recurring) + portal klienta

---

## 5) Frontend dashboardów

### Technologia
- React (opcjonalnie) lub plain PHP templates w GeneratePress Child Theme
- AJAX-first dla akcji (ulubione, subskrypcje)
- REST API jako backend

### Komponenty UI
- Sidebar nawigacja per rola
- Header z awatarem + powiadomieniami
- Karty KPI (metryki per widok)
- Tabele z filtrowaniem i paginacją
- Formularze z inline walidacją
- Toast notifications (sukces/błąd)

### URL struktura
- `/dashboard/` – dispatcher (redirect per rola)
- `/dashboard/user/` – User Dashboard
- `/dashboard/specialist/` – Specialist Dashboard
- `/dashboard/advertiser/` – Advertiser Dashboard
- `/dashboard/admin/` – Admin Dashboard (lub WP Admin)

---

## 6) Moduły domenowe (nowe)

### 6.1 UserProfile Module
Klasy:
- `Domain/User/ProfileRepository` – CRUD profilu
- `Domain/User/FavoritesService` – zapis/odczyt ulubionych
- `Domain/User/HistoryTracker` – historia przeglądania

Tabele:
- `user_favorites` (user_id, post_id, created_at)
- `user_history` (user_id, post_id, viewed_at)
- `user_subscriptions` (user_id, topic_slug, created_at)

### 6.2 SpecialistProfile Module
Klasy:
- `Domain/Specialist/ProfileRepository` – rozszerzony profil eksperta
- `Domain/Specialist/ContentWorkflow` – submit/review/publish
- `Domain/Specialist/EarningsService` – kalkulacja prowizji

Tabele:
- `specialist_profiles` (user_id, bio, specializations, socials, status)
- `specialist_earnings` (user_id, amount, source, period, status)

### 6.3 Notifications Module
Klasy:
- `Domain/Notification/NotificationService` – wysyłka + log
- `Domain/Notification/EmailDispatcher` – wp_mail wrapper

Tabele:
- `platform_notifications` (user_id, type, title, body, read_at, created_at)

---

## 7) Rozszerzenie REST API

Nowe endpointy:
```
GET  /wp-json/poradnik/v1/user/favorites
POST /wp-json/poradnik/v1/user/favorites
DELETE /wp-json/poradnik/v1/user/favorites/{id}
GET  /wp-json/poradnik/v1/user/history
GET  /wp-json/poradnik/v1/user/subscriptions
POST /wp-json/poradnik/v1/user/subscriptions

GET  /wp-json/poradnik/v1/specialist/profile
PUT  /wp-json/poradnik/v1/specialist/profile
GET  /wp-json/poradnik/v1/specialist/posts
POST /wp-json/poradnik/v1/specialist/posts
GET  /wp-json/poradnik/v1/specialist/stats
GET  /wp-json/poradnik/v1/specialist/earnings

GET  /wp-json/poradnik/v1/admin/platform-overview
GET  /wp-json/poradnik/v1/admin/users
PUT  /wp-json/poradnik/v1/admin/users/{id}/role
```

---

## 8) Rozszerzenie bazy danych

Nowe tabele:
- `user_favorites` – ulubione artykuły per user
- `user_history` – historia przeglądania (opt-in)
- `user_subscriptions` – subskrypcje per topic
- `specialist_profiles` – rozszerzony profil eksperta
- `specialist_earnings` – prowizje i wypłaty
- `platform_notifications` – powiadomienia per user

Wszystkie tabele: `id`, `created_at`, `updated_at`, indeksy pod `user_id`, `post_id`, `created_at`.

---

## 9) Security i compliance (Dashboard SaaS)

- RBAC na wszystkich endpointach (permission_callback per rola)
- Nonce dla każdej akcji AJAX
- Rate limiting dla endpointów publicznych (favorites, history)
- GDPR: możliwość eksportu i usunięcia danych użytkownika
- Audyt logu dla operacji finansowych (payments, earnings)
- Stripe PCI compliance – brak przechowywania danych karty

---

## 10) Struktura modułów (nowe)

```
platform-core/
  Modules/
    UserDashboard/       <- nowy
    SpecialistDashboard/ <- nowy
    Notifications/       <- nowy
  Domain/
    User/                <- nowy (ProfileRepository, FavoritesService, HistoryTracker)
    Specialist/          <- nowy (ProfileRepository, ContentWorkflow, EarningsService)
    Notification/        <- nowy (NotificationService, EmailDispatcher)
  Admin/
    UserDashboardPage.php    <- nowy (widok Admin: zarządzanie userami)
    SpecialistAdminPage.php  <- nowy (widok Admin: moderacja specjalistów)
  Api/Controllers/
    UserController.php          <- nowy
    SpecialistController.php    <- nowy
    AdminPlatformController.php <- nowy
```

---

## 11) Roadmap wdrożenia Dashboard.PRO

### Faza 1 – User Dashboard
- Rejestracja custom ról i capabilities
- Tabele: favorites, history, subscriptions
- REST API: user endpoints
- Frontend: User Dashboard pages (/dashboard/user/)
- Ulubione + historia (AJAX)

### Faza 2 – Specialist Dashboard
- Formularz zgłoszenia specjalisty
- Profil publiczny + schema Person
- Content workflow: submit → review → publish
- REST API: specialist endpoints
- Frontend: Specialist Dashboard (/dashboard/specialist/)

### Faza 3 – Admin Dashboard Extension
- Platform Overview KPI
- User Management panel
- Revenue Reports per kanał
- System Health monitor

### Faza 4 – Monetization SaaS (opcjonalna)
- Stripe Subscriptions: Free / Pro / Expert
- Portal klienta (manage billing)
- Gated content per plan

---

## 12) Definition of Done (Dashboard.PRO)

Każdy dashboard jest „production-ready", jeśli:
- Ma jawny RBAC (capability check per endpoint i widok)
- REST API endpointy mają walidację wejścia i permission_callback
- Tabele DB mają migrację przez dbDelta
- Frontend ma mobile-first responsive layout
- Zdarzenia domenowe są emitowane przez EventLogger
- Formulary mają nonce + sanitizację
- Dokumentacja techniczna i QA checklist są dostarczone

---

Ten dokument stanowi rozszerzenie PORADNIK-PRO-PLATFORM-BLUEPRINT.md o wielorolową warstwę SaaS (Dashboard.PRO), przeznaczoną do implementacji po ukończeniu Fazy AI + SEO platformy bazowej.
