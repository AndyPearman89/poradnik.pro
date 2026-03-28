# Poradnik Pro

Skonsolidowane repozytorium platformy **poradnik.pro**: motyw GeneratePress (frontend) oraz MU-plugin z logiką platformy (backend). Repo obejmuje kluczowe obszary produktu: Marketing Engine, AI Layer, Full Flow, Listings/Search/Q&A UI, SVG Pack i Pro Theme (szczegóły w `docs/specs/`).

## Zawartość

- `theme/` - motyw potomny GeneratePress z assetami i szablonami (poradniki, recenzje, porównania, rankingi, Q&A).
- `backend/` - MU-plugin (`platform-core`) z kernelami REST, modułami (affiliation, ads, AI, leads, specialists) i migracją bazy.
- `migrations/` - dokumentacja schematu SQL; migracje odpalane automatycznie przez `Migrator::maybeMigrate()`.
- `docs/` - instrukcje wdrożeniowe (`docs/cloud-deployment.md`) i specyfikacje domenowe (`docs/specs/*.md`).
- `poradnik-platform-loader.php` - loader MU-plugina.

## Wymagania

| Komponent | Wersja |
|-----------|--------|
| PHP | 8.1+ |
| WordPress | 6.4+ |
| MySQL / MariaDB | 8.0 / 10.6 |
| GeneratePress (parent) | 3.x |
| ACF Pro | 6.x |

Rekomendowane: Redis object cache, WP-CLI, klucze Search Console/Analytics, `OPENAI_API_KEY` dla Content Engine 3.0.

## Szybki start (WordPress)

1) Zainstaluj WordPress z wymaganymi wersjami i motywem nadrzędnym GeneratePress.  
2) Skopiuj repo do `wp-content` zgodnie z mapowaniem:
   - `theme/` -> `wp-content/themes/generatepress-child-poradnik/`
   - `backend/` -> `wp-content/mu-plugins/platform-core/`
   - `poradnik-platform-loader.php` -> `wp-content/mu-plugins/poradnik-platform-loader.php`
3) Ustaw sekrety w `wp-config.php` (DB, `WP_REDIS_HOST`, Stripe: `PORADNIK_STRIPE_*`, AI: `PORADNIK_OPENAI_API_KEY`).  
4) Aktywuj ACF Pro i (opcjonalnie) włącz potrzebne moduły platformy przez flagi w opcji `poradnik_platform_module_flags`.  
5) Migracje bazy uruchomią się na `init`; do wymuszenia ponownej migracji usuń `poradnik_platform_db_version` (WP-CLI) i przeładuj stronę.

## Testy

- Walidacja struktury repo: `php tests/e2e/validate-structure.php`
- REST smoke (wymaga WP-CLI i URL środowiska): `WP_URL=https://twoja-domena bash tests/e2e/rest-smoke.sh`

Opis testów: `tests/e2e/README.md`.

## Dokumentacja i wdrożenia

- Wdrożenia chmurowe i szczegóły środowisk: `docs/cloud-deployment.md`
- Specyfikacje produktowe: `docs/specs/`
- Migracje bazy i schemat: `migrations/README.md`
