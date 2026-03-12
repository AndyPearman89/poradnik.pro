# peartree.pro SEO Engine — Migration Notes

## Kompatybilność
- Zachowano istniejące menu admin `peartree-pro-seo-engine`.
- Zachowano istniejące ustawienia (`PSE_OPTION_KEY`) i harmonogramy cron.
- Brak zmian w publicznych URL wpisów i slugach.

## Nowe endpointy (admin-only)
- `GET /wp-json/pse/v1/status`
- `GET /wp-json/pse/v1/kpis?days=7`

## Zmiany wewnętrzne
- Dodane funkcje:
  - `pse_rest_permissions()`
  - `pse_rest_get_status()`
  - `pse_rest_get_kpis()`
  - `pse_register_rest_routes()`

## Checklist po wdrożeniu
1. Jako administrator wywołaj `pse/v1/status` i sprawdź status harmonogramów.
2. Wywołaj `pse/v1/kpis?days=7` i porównaj KPI z dashboardem SEO Engine.
3. Zweryfikuj, że generowanie treści i istniejące akcje admin działają bez zmian.
