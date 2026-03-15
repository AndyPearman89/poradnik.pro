# generatepress-child-poradnik

WordPress child theme dla **poradnik.pro**  platforma poradnikowa oparta na PearTree Core.

## Stack

- **Parent theme**: [GeneratePress](https://generatepress.com/) (wymagany)
- **Platform**: PearTree Core (`peartree/v1` REST API)
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
 functions.php     # rejestracja CPT, taksonomii, enqueue
 front-page.php    # strona główna (12 sekcji)
 header.php / footer.php
 style.css         # deklaracja motywu podrzędnego
```

## CPT (Custom Post Types)

| CPT | Slug |
|-----|------|
| Poradnik | `poradnik` |
| Ranking | `ranking` |
| Recenzja | `recenzja` |
| Porównanie | `porownanie` |
| Produkt | `produkt` |
| Specjalista | `specjalista` |
| Pytanie | `pytanie` |

## Taksonomie

- `kategoria`, `tag`, `usluga`, `miasto`

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

- Plugin: `peartree-core` (aktywny)
- REST API: `peartree/v1` dostępne na `/wp-json/peartree/v1/`
- Permalinks: ustawione na `/%postname%/`

## Wersjonowanie

Format: `MAJOR.MINOR.PATCH` w `style.css`  pole `Version:`

| Tag | Opis |
|-----|------|
| v1.0.0 | Init  full theme scaffold, 137 plików |

## Produkcja

- **URL**: https://poradnik.pro
- **Server**: `164.92.229.60`
- **WP path**: `/var/www/html`
- **Deploy script**: [`scripts/release/deploy-generatepress-child-poradnik.ps1`](https://github.com/AndyPearman89/PearTree_core) w PearTree_core repo
