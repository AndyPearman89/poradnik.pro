# peartree.pro SEO Engine — Audit Report

## Zakres
- Plugin: `peartree-pro-seo-engine`
- Wersja: `1.1.0`
- Data audytu: 2026-03-12

## Architektura (stan po zmianach)
- Plugin oparty o jeden plik główny + moduły: `internal-links.php`, `schema.php`, `meta.php`, `breadcrumbs.php`, `related.php`.
- Zawiera generator treści, harmonogramy cron, metryki jakości i panel admin.
- Dodano warstwę REST status/KPI do monitoringu operacyjnego.

## Usprawnienia enterprise
1. Dodano endpoint statusowy admin-only:
   - `GET /wp-json/pse/v1/status`
2. Dodano endpoint KPI admin-only:
   - `GET /wp-json/pse/v1/kpis?days=7`
3. Endpointy raportują SLA harmonogramów, aktywność generatora i KPI z logu audytowego.

## Bezpieczeństwo
- Endpointy REST mają `permission_callback` oparte o `manage_options`.
- Parametr `days` jest walidowany i ograniczony do bezpiecznego zakresu.

## Ryzyka
- Duży monolit (`peartree-pro-seo-engine.php`) utrudnia testowalność i utrzymanie.
- Część logiki generatora i UI admin jest silnie sprzężona.

## Rekomendowane następne kroki
- Rozbić plugin na warstwy `src/Admin`, `src/Application`, `src/Rest`.
- Przenieść logikę REST do dedykowanych klas kontrolerów.
- Dodać testy regresji dla generatora i SLA harmonogramów.
