# peartree.pro Ads Marketplace — Audit Report

## Zakres
- Plugin: `peartree-pro-ads-marketplace`
- Wersja: `1.0.0`
- Data audytu: 2026-03-12

## Architektura (stan po zmianach)
- `core/` — logika domenowa kampanii, statusów, metryk i REST stats.
- `admin/` — zarządzanie slotami, kampaniami, zamówieniami i testami webhooków.
- `payments/` — webhook Stripe/PayPal + przepływ potwierdzeń płatności.
- `frontend/` — formularz kampanii, panel reklamodawcy, sloty i landing.
- `analytics/` — kliknięcia i CTR.

## Najważniejsze ryzyka i decyzje
- **Monolit admin page**: główna strona admin zawiera wiele sekcji; pozostawiono kompatybilność slugów.
- **Webhook security**: obecnie token/secrets per-opcja; potwierdzono walidację sygnatur/tokenów.
- **Wydajność**: metryki były liczone rozproszonymi zapytaniami; dodano centralne metody KPI.

## Zrealizowane usprawnienia
1. Dodano centralne KPI kampanii w `CampaignManager::getOverviewStats()`.
2. Dodano listę ostatnich kampanii przez `CampaignManager::getRecentCampaigns()`.
3. Dodano REST API admin-only:
   - `GET /wp-json/ppam/v1/stats`
   - `GET /wp-json/ppam/v1/campaigns?limit=10`
4. Dodano sekcję dashboardową z KPI i ostatnimi kampaniami w panelu `ppam-marketplace`.

## Bezpieczeństwo
- REST endpoints dla metryk mają `permission_callback` oparte o `manage_options`.
- Dane wejściowe endpointów (np. `limit`) są sanityzowane i ograniczone.

## Kolejne kroki (opcjonalne)
- Przenieść render admin na osobne klasy widoków/templating.
- Dodać cache transients dla agregacji KPI przy dużej liczbie kampanii.
- Dodać wersjonowanie odpowiedzi REST (`schema`/`version`).
