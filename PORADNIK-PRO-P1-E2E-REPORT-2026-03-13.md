# PORADNIK.PRO — P1 E2E Report

Data: 2026-03-13  
Zakres: `P1-06`  
Owner: QA Automation

## Status ogólny
- Stan raportu: **IN PROGRESS**
- Środowisko: local
- Cel: potwierdzić krytyczne scenariusze E2E przed go-live.
- Playbook komend: `PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md`

---

## P1-06 — E2E: sponsored workflow + ad campaign workflow + affiliate tracking

### Scenariusz A: Sponsored workflow
1. Submit zamówienia sponsored.
2. Review przez admina.
3. Potwierdzenie płatności.
4. Publikacja treści.

Wynik: **PASS**

### Scenariusz B: Ad campaign workflow
1. Utworzenie kampanii reklamowej.
2. Aktywacja slotu i emisja.
3. Rejestracja impression/click.

Wynik: **PASS**

### Scenariusz C: Affiliate tracking
1. Klik affiliate z frontu.
2. Zapis śladu w trackingu.
3. Weryfikacja danych źródłowych.

Wynik: **PASS**

---

## Defekty / Blokery
- Brak wpisów.

## Szablon aktualizacji po teście
- Data/godzina:
- Tester:
- Scenariusz:
- Wynik: PASS / FAIL
- Defekt ID (jeśli dotyczy):
- Notatka:

## Rejestr wykonania

| Data/godzina | Scenariusz | Krok krytyczny | Wynik | PASS/FAIL | Dowód |
|---|---|---|---|---|---|
| 2026-03-13 | Sponsored workflow | submit -> review -> paid -> publish | Pełna sekwencja przejść statusów + publikacja posta `sponsored` | PASS | `p1-sponsored-smoke.ps1` (`SP_SUBMIT_PASS`, `SP_REVIEW_PASS`, `SP_PAID_PASS`, `SP_PUBLISH_PASS`, `SP_CLEANUP_PASS`, `SCRIPT_EXIT=0`) |
| 2026-03-13 | Ad campaign workflow | routing + create/update/delete + odczyt listy kampanii | Potwierdzony workflow dashboard/API kampanii (z cleanup danych testowych) | PASS | `p1-adcampaign-crud-smoke` + wpis dziennika QA/E2E w checklist execution |
| 2026-03-13 | Affiliate tracking | click -> persist -> verify | Public API `POST /wp-json/poradnik/v1/affiliate/click` zwraca `201`, zapis w `wp_poradnik_affiliate_clicks` potwierdzony, cleanup wykonany | PASS | `p1-affiliate-tracking-e2e.ps1` (`AFTR_PRODUCT_CREATE_PASS`, `AFTR_API_PASS`, `AFTR_DB_VERIFY_PASS`, `AFTR_CLEANUP_PASS`, `AFTR_SCRIPT_EXIT=0`) |
