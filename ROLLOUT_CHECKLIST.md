# Ads Marketplace â€” Rollout Checklist (Production)

## 1) Pre-deploy
- [ ] PotwierdĹş aktywny motyw: `Poradnik.pro Portal (1.0.0)`.
- [ ] PotwierdĹş aktywne pluginy:
  - [ ] `peartree-pro-ads-marketplace`
  - [ ] `peartree-pro-platform`
- [ ] Wykonaj backup bazy i plikĂłw (`wp-content/plugins`, `wp-content/themes`).
- [ ] SprawdĹş, czy strony istniejÄ…:
  - [ ] `/oferty-sponsorowane/`
  - [ ] `/panel-reklamodawcy/`

## 2) Templates i integracja z motywem
- [ ] Zweryfikuj przypisanie template:
  - [ ] `/oferty-sponsorowane/` â†’ `template-marketplace-ads.php`
  - [ ] `/panel-reklamodawcy/` â†’ `template-marketplace-panel.php`
- [ ] OtwĂłrz oba URL i sprawdĹş render:
  - [ ] sekcje landingu,
  - [ ] panel reklamodawcy,
  - [ ] styl zgodny z motywem (kontener `pp-container`, spacing mobile/desktop).

## 3) Webhooki pĹ‚atnoĹ›ci
### Stripe
- [ ] Endpoint: `/wp-json/ppam/v1/webhook/stripe`
- [ ] Ustaw poprawny `stripe_signing_secret` w `admin.php?page=ppam-marketplace`.
- [ ] SprawdĹş nagĹ‚Ăłwek `stripe-signature` i odpowiedĹş HTTP 200.

### PayPal
- [ ] Endpoint: `/wp-json/ppam/v1/webhook/paypal`
- [ ] Ustaw `paypal_webhook_token` w `admin.php?page=ppam-marketplace`.
- [ ] WysyĹ‚aj nagĹ‚Ăłwek `X-PPAM-PayPal-Token`.
- [ ] SprawdĹş odpowiedĹş HTTP 200.

## 4) Flow kampanii (E2E)
- [ ] UtwĂłrz kampaniÄ™ z `/panel-reklamodawcy/`.
- [ ] Status po utworzeniu: `pending_payment`.
- [ ] Zrealizuj pĹ‚atnoĹ›Ä‡ testowÄ… (Stripe/PayPal).
- [ ] Status po webhooku: `pending_approval`.
- [ ] Akceptacja admina w `admin.php?page=ppam-campaigns`.
- [ ] Status koĹ„cowy: `active`.

## 5) Emisja i statystyki
- [ ] Zweryfikuj emisjÄ™ slotĂłw (`header`, `article`, `footer`, sponsorowane/rankingowe).
- [ ] Kliknij reklamÄ™ i potwierdĹş wzrost licznikĂłw klikniÄ™Ä‡.
- [ ] SprawdĹş CTR i widocznoĹ›Ä‡ danych w panelach admin/reklamodawca.

## 6) Dashboard integracyjny
- [ ] OtwĂłrz `admin.php?page=ppp-dashboard&tab=ads`.
- [ ] SprawdĹş KPI marketplace (kampanie, aktywne, pending payment, budĹĽet).
- [ ] SprawdĹş szybkie linki do `ppam-marketplace`, `ppam-campaigns`, `ppam-orders`.

## 7) Smoke po wdroĹĽeniu
- [ ] Brak bĹ‚Ä™dĂłw PHP w logach.
- [ ] Brak bĹ‚Ä™dĂłw JS/CSS w konsoli dla landingu/panelu.
- [ ] Brak bĹ‚Ä™dĂłw 4xx/5xx dla endpointĂłw webhook.
- [ ] Cron wygaszania kampanii dziaĹ‚a (`ppam_expire_campaigns_event`).

## 8) Rollback (awaryjnie)
- [ ] WyĹ‚Ä…cz `peartree-pro-ads-marketplace`.
- [ ] PrzywrĂłÄ‡ backup DB + plikĂłw plugin/theme.
- [ ] Zweryfikuj dziaĹ‚anie stron krytycznych i panelu admin.
- [ ] PrzywrĂłÄ‡ poprzednie sekrety webhook i potwierdĹş brak retry storm po stronie dostawcy pĹ‚atnoĹ›ci.

## 9) Done criteria
- [ ] Kampania testowa przeszĹ‚a caĹ‚y flow do `active`.
- [ ] Landing i panel reklamodawcy renderujÄ… poprawnie desktop/mobile.
- [ ] Dashboard `ppp-dashboard` pokazuje KPI i linki bez bĹ‚Ä™dĂłw.
- [ ] Brak bĹ‚Ä™dĂłw runtime przez min. 24h monitoringu.
