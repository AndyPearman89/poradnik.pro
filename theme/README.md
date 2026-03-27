# generatepress-child-poradnik

WordPress child theme dla **poradnik.pro** jako niezaleznej platformy zorientowanej na Content Engine 3.0, Q&A, marketplace specjalistow, afiliacje i lead generation.

## Stack

- **Parent theme**: [GeneratePress](https://generatepress.com/) (wymagany)
- **Platform**: standalone WordPress + REST API `poradnik/v1` oraz `peartree/v1`
- **PHP**: 8.1+
- **WordPress**: 6.4+

## Struktura

```
generatepress-child-poradnik/
 assets/
    css/          # main, layout, components, responsive
    js/           # main, search, ajax, filters
 template-parts/
    front-page/   # sekcje strony głównej (hero, latest, rankings...)
    content/      # single templates per CPT
    lead/         # lead form, popup, confirmation
    listing/      # karty specjalistów
    schema/       # JSON-LD schema.org
    ui/           # komponenty (card, button, modal, badge, alert)
 functions.php     # setup motywu, enqueue, integracja z REST i render komponentow platformy
 front-page.php    # strona główna (12 sekcji)
 header.php / footer.php
 style.css         # deklaracja motywu podrzędnego
```

## Core Content Types (platforma 3.0)

| CPT | Slug |
|-----|------|
| Poradnik | `poradnik` |
| Ranking | `ranking` |
| Recenzja | `recenzja` |
| Porównanie | `porownanie` |
| Specjalista | `specjalista` |
| Pytanie | `pytanie` |
| Odpowiedź | `odpowiedz` |

## Auxiliary Entities

| Encja | Slug |
|-----|------|
| Produkt | `produkt` |

## Taksonomie

- `kategoria`, `tag`, `usluga`, `miasto`

## Zakres UI

- landing pages dla poradnikow, recenzji, porownan i rankingow
- komponenty Q&A i sekcje przejsc do specjalistow
- sticky CTA dla leadow i afiliacji
- responsive comparison tables i boksy eksperckie

## Instalacja na serwerze

```bash
# 1. Skopiuj folder motywu na serwer
rsync -avz ./ root@164.92.229.60:/var/www/html/wp-content/themes/generatepress-child-poradnik/

# 2. Upewnij się że parent theme jest zainstalowany
wp theme install generatepress --activate --allow-root

# 3. Aktywuj child theme
wp theme activate generatepress-child-poradnik --allow-root

# 4. Flush cache
wp cache flush --allow-root
```

## Wymagania runtime

- MU Plugin: `platform-core` (backend) zaladowany przez `poradnik-platform-loader.php`
- REST API: endpointy dostępne pod `/wp-json/poradnik/v1/` i `/wp-json/peartree/v1/`
- Permalinks: ustawione na `/%postname%/`

## Lokalna walidacja (build)

Walidacja zgodna z pipeline CI (`.github/workflows/deploy.yml`):

```bash
# Lint PHP backendu (wszystkie pliki *.php)
find backend -type f -name '*.php' -print0 | xargs -0 -n1 php -l

# Walidacja struktury repo (required files/dirs)
php tests/e2e/validate-structure.php
```

## Wersjonowanie

Format: `MAJOR.MINOR.PATCH` w `style.css`  pole `Version:`

| Tag | Opis |
|-----|------|
| v2.0.0 | Aktualna wersja motywu (zgodna z `theme/style.css`) |

## Produkcja

- **URL**: https://poradnik.pro
- **Server**: `164.92.229.60`
- **WP path**: `/var/www/html`
- **Deploy script (repo)**: `deploy.sh`
