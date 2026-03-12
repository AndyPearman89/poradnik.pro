# peartree.pro Platform — Migration Notes

## Kompatybilność
- Nie zmieniono istniejących slugów menu ani tabów dashboardu.
- Nie zmieniono istniejących hooków i synchronizacji ustawień.

## Nowe endpointy (admin-only)
- `GET /wp-json/ppp/v1/status`
- `GET /wp-json/ppp/v1/kpis`

## Zmiany wewnętrzne
- Dodano funkcje REST:
  - `ppp_rest_permissions()`
  - `ppp_register_rest_routes()`
  - `ppp_rest_get_status()`
  - `ppp_rest_get_kpis()`
- Endpointy raportują status integracji i KPI dashboardowe bez modyfikacji istniejącego UI.

## Checklist po wdrożeniu
1. Jako administrator wywołaj `ppp/v1/status` i `ppp/v1/kpis`.
2. Potwierdź, że dashboard działa jak wcześniej (`ppp-dashboard`).
3. Sprawdź, że integracje z `PPAM`, `PAA` i `PPAE` nadal raportują poprawne statusy.
