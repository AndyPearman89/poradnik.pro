# Poradnik Afilacja i AdSense

Lekki plugin WordPress do monetyzacji portalowej:
- Google AdSense (z placementami),
- zarzÄ…dzanie linkami afiliacyjnymi,
- redirect `/go/{slug}` z trackingiem klikniÄ™Ä‡,
- shortcode dla reklam i boxĂłw afiliacyjnych.

## Funkcje

- Panel admin: **PearTree Monetization**
	- AdSense Settings
	- Affiliate Links
	- Click Statistics
- Tabele DB tworzone na aktywacji:
	- `wp_peartree_affiliate_links`
	- `wp_peartree_affiliate_clicks`
- REST API: `GET /wp-json/peartree/v1/affiliate/stats`
- Caching: transients dla listy linkĂłw i statystyk.

## Instalacja

1. UmieĹ›Ä‡ folder `peartree-pro-afiliacja-adsense` w `wp-content/plugins/`.
2. Aktywuj plugin w panelu WordPress.
3. WejdĹş do menu **PearTree Monetization**.

## AdSense

W **AdSense Settings** ustaw:
- AdSense Publisher ID,
- AdSense Script,
- Enable Auto Ads.

ObsĹ‚ugiwane placementy:
- `header`
- `sidebar`
- `article_top`
- `article_middle`
- `article_bottom`
- `footer`

Shortcode:
- `[peartree_adsense placement="article_top"]`
- alias: `[paa_adsense placement="article_top"]`

## Afiliacja

W **Affiliate Links** dodajesz/edytujesz/usuwasz linki:
- title
- slug
- destination_url
- category
- description
- button_text
- image_url (opcjonalnie)

Frontend redirect:
- `/go/hosting`

Shortcode:
- `[peartree_affiliate id="12"]`
- `[peartree_affiliate_box id="12"]`
- aliasy: `[paa_affiliate id="12"]`, `[paa_affiliate_box id="12"]`

## BezpieczeĹ„stwo

- Nonce dla akcji admin.
- Sanitizacja wejĹ›cia (`sanitize_text_field`, `sanitize_title`, `esc_url_raw`, `sanitize_textarea_field`).
- Zapytania SQL z prepared statements.

## Struktura

- `peartree-pro-afiliacja-adsense.php`
- `src/Core/*`
- `src/Adsense/*`
- `src/Affiliate/*`
- `src/Infrastructure/AffiliateRepository.php`
- `src/Frontend/*`
- `src/Admin/*`
- `src/Api/StatsEndpoint.php`
- `templates/*`
- `assets/css/affiliate.css`
- `assets/js/affiliate.js`

