# peartree.pro Ads Marketplace — Migration Notes

## Kompatybilność
- Zachowano dotychczasowe slugi admin:
  - `ppam-marketplace`
  - `ppam-campaigns`
  - `ppam-orders`
- Zachowano istniejące webhook endpointy:
  - `ppam/v1/webhook/stripe`
  - `ppam/v1/webhook/paypal`

## Nowe endpointy (admin-only)
- `GET /wp-json/ppam/v1/stats`
- `GET /wp-json/ppam/v1/campaigns?limit=10`

## Zmiany wewnętrzne
- Dodano nowe metody w `CampaignManager`:
  - `getOverviewStats()`
  - `getRecentCampaigns(int $limit = 10)`
- Dodano nową klasę: `core/StatsController.php`.
- Rozszerzono UI strony `ppam-marketplace` o sekcję KPI i ostatnie kampanie.

## Checklist po wdrożeniu
1. Wejdź do panelu `Marketplace Reklam` i sprawdź sekcję KPI.
2. Wywołaj endpointy REST jako administrator i zweryfikuj odpowiedź JSON.
3. Potwierdź, że webhooki Stripe/PayPal działają bez zmian.
