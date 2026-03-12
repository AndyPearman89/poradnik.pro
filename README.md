# Poradnik Pro Platform â€” README

## Zakres
Plugin odpowiada za dashboard `Poradnik.pro` (`wp-admin/admin.php?page=ppp-dashboard`) oraz centralne ustawienia treĹ›ci, monetyzacji i integracji SEO.

## Ostatnie zmiany (marzec 2026)

### 1) Integracja â€žReklamy i afiliacjaâ€ť z Ads Marketplace
W zakĹ‚adce **Reklamy i afiliacja** dodano blok **Integracja Marketplace Reklam**, ktĂłry:
- wykrywa aktywnoĹ›Ä‡ moduĹ‚u Ads Marketplace,
- pokazuje KPI kampanii reklamowych (Ĺ‚Ä…cznie, aktywne, oczekujÄ…ce na pĹ‚atnoĹ›Ä‡, Ĺ‚Ä…czny budĹĽet),
- udostÄ™pnia szybkie linki do:
  - `admin.php?page=ppam-marketplace`,
  - `admin.php?page=ppam-campaigns`,
  - `admin.php?page=ppam-orders`,
  - `/panel-reklamodawcy/`,
  - `/oferty-sponsorowane/`.

### 2) Hardening wejĹ›cia (security)
Ujednolicono odczyt danych wejĹ›ciowych (`$_GET`, `$_POST`) przez:
- `wp_unslash(...)` przed dalszÄ… obrĂłbkÄ…,
- sanitacjÄ™ (`sanitize_key`, `sanitize_text_field`, rzutowania numeryczne),
- bezpiecznÄ… walidacjÄ™ nonce.

Dotyczy to m.in.:
- routingu zakĹ‚adek dashboardu,
- akcji resetu statystyk i generatora,
- obsĹ‚ugi redirectĂłw klikniÄ™Ä‡,
- zapisu metaboxa rankingu.

### 3) Optymalizacja KPI Ads Marketplace
Refaktoryzacja liczenia KPI kampanii:
- usuniÄ™to kosztownÄ… pÄ™tlÄ™ po wszystkich kampaniach z wieloma `get_post_meta`,
- dodano agregacjÄ™ licznikĂłw statusĂłw przez zapytania `WP_Query` (`found_posts`),
- dodano sumowanie budĹĽetu jednym zapytaniem SQL `SUM(...)`.

### 4) Cache KPI
Dodano cache wynikĂłw KPI Ads Marketplace:
- transient: `ppp_ads_marketplace_summary_v1`,
- TTL: `60` sekund,
- invalidacja po zmianach kampanii (`save_post_ppam_campaign`, `deleted_post`, `trashed_post`, `untrashed_post`).

## Plik kluczowy
- `peartree-pro-platform.php`

## Szybka weryfikacja po wdroĹĽeniu
1. OtwĂłrz: `wp-admin/admin.php?page=ppp-dashboard&tab=ads`.
2. SprawdĹş sekcjÄ™ â€žIntegracja Marketplace Reklamâ€ť oraz KPI.
3. Zweryfikuj szybkie linki do podstron Ads Marketplace.
4. PotwierdĹş zapis ustawieĹ„ w zakĹ‚adce Ads.
5. SprawdĹş brak bĹ‚Ä™dĂłw skĹ‚adni:
   - `php -l wp-content/plugins/peartree-pro-platform/peartree-pro-platform.php`

## Uwagi
- JeĹ›li Ads Marketplace jest nieaktywny, dashboard pokazuje komunikat ostrzegawczy i link do listy pluginĂłw.
- KPI sÄ… odĹ›wieĹĽane automatycznie po zmianach kampanii (invalidacja transienta).
