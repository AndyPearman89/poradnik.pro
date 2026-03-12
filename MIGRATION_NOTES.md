# peartree.pro Afilacja i AdSense — Migration Notes

## Kompatybilność
- Zachowano istniejące slugi admin:
  - `paa-monetization`
  - `paa-settings`
  - `paa-affiliate-links`
  - `paa-click-statistics`
- Zachowano istniejące endpointy i tracking linków `/go/{slug}`.

## REST API
- Istniejący endpoint: `GET /wp-json/peartree/v1/affiliate/stats`
- Nowy endpoint: `GET /wp-json/peartree/v1/affiliate/health`

## Zmiany wewnętrzne
- Rozszerzono `StatsEndpoint` o `handleGetHealth()`.
- Rozszerzono `AdminMenu::renderOverviewPage()` o KPI oraz adresy endpointów REST.

## Checklist po wdrożeniu
1. Wejdź do `peartree.pro Monetization` i sprawdź widoczność KPI.
2. Jako administrator wywołaj oba endpointy REST i zweryfikuj payload.
3. Potwierdź, że zapis linków i tracking kliknięć działa jak wcześniej.
