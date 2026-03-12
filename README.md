# Poradnik Ads Marketplace â€” README

## Zakres
Plugin obsĹ‚uguje marketplace reklam dla Poradnik.pro:
- kampanie reklamowe,
- sloty reklamowe,
- panel reklamodawcy,
- pĹ‚atnoĹ›ci (Stripe/PayPal, webhook + return),
- statystyki (wyĹ›wietlenia, klikniÄ™cia, CTR),
- zaplecze administracyjne.

## GĹ‚Ăłwne moduĹ‚y
- `core/Marketplace.php` â€” bootstrap moduĹ‚u i rejestracje hookĂłw.
- `core/CampaignManager.php` â€” logika kampanii, statusy, wygaszanie.
- `admin/AdsInventory.php` â€” konfiguracja slotĂłw i webhookĂłw, test webhookĂłw.
- `admin/Campaigns.php` â€” zarzÄ…dzanie kampaniami + akcje admina + CSV.
- `admin/Orders.php` â€” lista zamĂłwieĹ„ reklamowych.
- `frontend/CampaignForm.php` â€” formularz tworzenia kampanii.
- `frontend/AdvertiserPanel.php` â€” panel reklamodawcy.
- `frontend/AdSlots.php` â€” render slotĂłw i linki trackowane.
- `payments/Stripe.php` â€” checkout URL, webhook, return handler.
- `payments/PayPal.php` â€” checkout URL, webhook, return handler.
- `analytics/Stats.php` â€” tracking klikniÄ™Ä‡ i CTR.

## Statusy kampanii
ObsĹ‚ugiwane statusy:
- `pending_payment`
- `pending_approval`
- `active`
- `paused`
- `completed`
- `rejected`

## PrzepĹ‚yw kampanii (skrĂłt)
1. Reklamodawca tworzy kampaniÄ™ (`CampaignForm`).
2. Kampania trafia na `pending_payment`.
3. Checkout Stripe/PayPal.
4. Webhook pĹ‚atnoĹ›ci ustawia `pending_approval`.
5. Admin akceptuje kampaniÄ™ (`active`) lub odrzuca (`rejected`).
6. Po dacie koĹ„cowej kampania moĹĽe zostaÄ‡ wygaszona do `completed`.

## Webhooki pĹ‚atnoĹ›ci
### Stripe
- Endpoint REST: `/wp-json/ppam/v1/webhook/stripe`
- Weryfikacja sygnatury: nagĹ‚Ăłwek `stripe-signature`
- Konfiguracja sekretu: `ppam_webhook_settings[stripe_signing_secret]`

### PayPal
- Endpoint REST: `/wp-json/ppam/v1/webhook/paypal`
- Weryfikacja tokenu: nagĹ‚Ăłwek `X-PPAM-PayPal-Token`
- Konfiguracja tokenu: `ppam_webhook_settings[paypal_webhook_token]`

## Strony tworzone przy aktywacji
- `/panel-reklamodawcy/` (`[ppam_advertiser_panel]`)
- `/oferty-sponsorowane/` (`[ppam_sponsored_landing]`)

## Mapa stron i szablonĂłw
- `/oferty-sponsorowane/`
	- template: `template-marketplace-ads.php`
	- shortcode: `[ppam_sponsored_landing]`
- `/panel-reklamodawcy/`
	- template: `template-marketplace-panel.php`
	- shortcode: `[ppam_advertiser_panel]`

Template sÄ… przypisywane automatycznie przez plugin (aktywacja + migracja kompatybilnoĹ›ci dla istniejÄ…cych instalacji).

## BezpieczeĹ„stwo i sanitacja
W newralgicznych punktach wejĹ›cia wdroĹĽono standard:
- `wp_unslash(...)` dla `$_GET` / `$_POST`,
- sanitacja (`sanitize_key`, `sanitize_text_field`, `esc_url_raw`, rzutowania),
- nonce check dla akcji formularzy.

## Integracja z Poradnik Pro Platform
W `Poradnik.pro Dashboard` (`ppp-dashboard`, zakĹ‚adka Ads) plugin jest integrowany przez:
- KPI kampanii (Ĺ‚Ä…cznie, aktywne, oczekujÄ…ce na pĹ‚atnoĹ›Ä‡, budĹĽet),
- szybkie linki do paneli `ppam-*` i stron frontend,
- obsĹ‚ugÄ™ stanu â€žplugin nieaktywnyâ€ť.

## Integracja z motywem
Plugin zawiera warstwÄ™ kompatybilnoĹ›ci z motywami frontu (klasy `body` + wrapper kontenera), w tym dedykowany preset wizualny dla:
- `Poradnik.pro Portal` (`poradnik-pro-portal`) â€” wersja `1.0.0`

W praktyce landing i komponenty PPAM dopasowujÄ…:
- kontener (`pp-container`),
- spacing sekcji,
- styl kart i CTA do tokenĂłw motywu (`--pp-*`).

## Warianty copy landingu
Landing ofert sponsorowanych wspiera warianty treĹ›ci przez filtr:
- filtr: `ppam_landing_copy_variant`
- dostÄ™pne wartoĹ›ci: `performance` (domyĹ›lny), `premium`

PrzykĹ‚ad wymuszenia wariantu premium:

```php
add_filter('ppam_landing_copy_variant', static function () {
	return 'premium';
});
```

## Szybki smoke-check
1. OtwĂłrz `admin.php?page=ppam-marketplace` i zapisz webhook settings.
2. OtwĂłrz `admin.php?page=ppam-campaigns` i zweryfikuj listÄ™ kampanii.
3. OtwĂłrz `admin.php?page=ppam-orders` i zweryfikuj zamĂłwienia.
4. Frontend: `/panel-reklamodawcy/` â€” utwĂłrz kampaniÄ™.
5. Frontend: slot reklamowy z linkiem trackowanym â€” potwierdĹş wzrost klikniÄ™Ä‡.

## Walidacja skĹ‚adni (lokalnie)
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/peartree-pro-ads-marketplace.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/core/CampaignManager.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/payments/Stripe.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/payments/PayPal.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/frontend/CampaignForm.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/frontend/AdvertiserPanel.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/admin/Campaigns.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/admin/AdsInventory.php`
- `php -l wp-content/plugins/peartree-pro-ads-marketplace/analytics/Stats.php`

## Uwagi operacyjne
- Bez dziaĹ‚ajÄ…cej bazy MySQL testy runtime przez CLI nie przejdÄ… (WordPress nie zbootuje siÄ™ przez `wp-load.php`).
- W Ĺ›rodowiskach Local upewnij siÄ™, ĹĽe usĹ‚ugi site sÄ… uruchomione przed testami E2E.
