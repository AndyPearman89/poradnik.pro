# GO LIVE â€” Ads Marketplace (15 min)

## 0) Cel
Szybkie uruchomienie produkcyjne moduĹ‚u Ads Marketplace z minimalnym ryzykiem.

## 1) Krytyczne pre-check (2 min)
- [ ] Aktywny motyw: `Poradnik.pro Portal 1.0.0`
- [ ] Aktywne pluginy:
  - [ ] `peartree-pro-ads-marketplace`
  - [ ] `peartree-pro-platform`
- [ ] Backup DB + `wp-content/plugins` + `wp-content/themes`

## 2) Strony i template (2 min)
- [ ] OtwĂłrz `/oferty-sponsorowane/` i potwierdĹş landing.
- [ ] OtwĂłrz `/panel-reklamodawcy/` i potwierdĹş panel.
- [ ] W razie braku template: edytuj stronÄ™ i wybierz wĹ‚aĹ›ciwy szablon.

## 3) Webhooki pĹ‚atnoĹ›ci (4 min)
- [ ] WejdĹş: `wp-admin/admin.php?page=ppam-marketplace`
- [ ] Ustaw i zapisz:
  - [ ] `stripe_signing_secret`
  - [ ] `paypal_webhook_token`
- [ ] Stripe endpoint: `/wp-json/ppam/v1/webhook/stripe`
- [ ] PayPal endpoint: `/wp-json/ppam/v1/webhook/paypal`
- [ ] WyĹ›lij test webhookĂłw z panelu i potwierdĹş sukces.

## 4) Test E2E kampanii (4 min)
- [ ] UtwĂłrz kampaniÄ™ z `/panel-reklamodawcy/`
- [ ] PotwierdĹş status `pending_payment`
- [ ] Symuluj/opĹ‚aÄ‡ pĹ‚atnoĹ›Ä‡ â†’ status `pending_approval`
- [ ] Akceptuj kampaniÄ™ w `wp-admin/admin.php?page=ppam-campaigns`
- [ ] PotwierdĹş status `active`

## 5) Smoke dashboard + emisja (3 min)
- [ ] OtwĂłrz: `wp-admin/admin.php?page=ppp-dashboard&tab=ads`
- [ ] SprawdĹş KPI i linki do `ppam-*`
- [ ] Kliknij aktywnÄ… reklamÄ™ i potwierdĹş przyrost klikniÄ™Ä‡/CTR

## 6) Go/No-Go
**GO** jeĹ›li:
- [ ] webhooki zwracajÄ… sukces,
- [ ] kampania przechodzi do `active`,
- [ ] landing/panel renderujÄ… poprawnie mobile+desktop,
- [ ] brak bĹ‚Ä™dĂłw 5xx/PHP w logach.

**NO-GO** jeĹ›li ktĂłrykolwiek warunek krytyczny nie jest speĹ‚niony.

## 7) Szybki rollback
- [ ] Dezaktywuj `peartree-pro-ads-marketplace`
- [ ] PrzywrĂłÄ‡ backup DB + plikĂłw
- [ ] Zweryfikuj kluczowe URL i panel admin
- [ ] Wstrzymaj retry webhookĂłw po stronie dostawcĂłw (jeĹ›li wymagane)

---
PeĹ‚na wersja procedury: `ROLLOUT_CHECKLIST.md`
