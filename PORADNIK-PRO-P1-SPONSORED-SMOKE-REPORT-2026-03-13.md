# PORADNIK.PRO — P1 Sponsored Smoke Report

Data: 2026-03-13  
Zakres: `P1-03`  
Owner: QA Lead

## Status ogólny
- Stan raportu: **DONE**
- Środowisko: local
- Cel: zweryfikować pełny sponsored workflow end-to-end na poziomie smoke.

---

## P1-03 — Smoke: sponsored workflow (submit -> review -> paid -> publish)

### Kroki testowe
1. Utworzenie zamówienia sponsored przez formularz/public endpoint.
2. Weryfikacja przejścia statusu `submit` -> `review`.
3. Symulacja/obsługa płatności i przejście do statusu `paid`.
4. Publikacja i weryfikacja statusu końcowego `publish`.
5. Kontrola logów błędów (brak HTTP 500/fatal).

### Wynik
- Status: **PASS**
- Uwagi: workflow wykonany na koncie technicznym `QAADMIN` przez stronę admin `Tools -> Sponsored Orders`; pełna sekwencja `submit -> review -> paid -> publish` potwierdzona.
- Dowód techniczny: `p1-sponsored-smoke.ps1` wynik `SP_SUBMIT_PASS`, `SP_REVIEW_PASS`, `SP_PAID_PASS`, `SP_PUBLISH_PASS`, `SP_CLEANUP_PASS`, `SCRIPT_EXIT=0`.

### Szczegóły walidacji
- Submit: rekord zamówienia utworzony w `wp_poradnik_sponsored_articles` ze statusem `submitted` i `payment_status=pending`.
- Review: przejście workflow ustawione na `status=review`, `payment_status=pending`.
- Paid: przejście workflow ustawione na `status=paid`, `payment_status=paid`.
- Publish: utworzony post typu `sponsored` ze statusem `publish`; zamówienie otrzymało `status=published` oraz `post_id > 0`.
- Cleanup: rekordy testowe (zamówienie + post + postmeta) usunięte po teście.

---

## Szablon aktualizacji po teście
- Data/godzina:
- Tester:
- Wynik: PASS / FAIL
- Defekt ID (jeśli dotyczy):
- Krótkie podsumowanie:
