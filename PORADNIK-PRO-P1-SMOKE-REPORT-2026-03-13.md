# PORADNIK.PRO — P1 Smoke Report

Data: 2026-03-13  
Zakres: `P1-01`, `P1-02`  
Owner: QA Lead

## Status ogólny
- Stan raportu: **DONE**
- Środowisko: local
- Cel: udokumentować smoke dla panelu Tools oraz CRUD affiliate/ad campaign

---

## P1-01 — Smoke: logowanie admin + dostęp do Tools

### Kroki testowe
1. Logowanie do WP Admin kontem z uprawnieniami administracyjnymi.
2. Wejście w sekcję `Tools`.
3. Otworzenie stron platformy (moduły narzędziowe).
4. Weryfikacja braku błędów krytycznych i błędów 500.

### Wynik
- Status: **PASS**
- Uwagi: utworzono konto techniczne `QAADMIN`, logowanie do `/wp-admin/` poprawne, dostęp do `tools.php` potwierdzony, brak HTTP 500.
- Dowód techniczny: wynik terminala `TOOLS_ACCESS=PASS` + HTML odpowiedzi admin (`qaadmin-tools.html`).

---

## P1-02 — Smoke: CRUD affiliate product i CRUD ad campaign

### Kroki testowe
1. Affiliate Product: create -> read -> update -> delete.
2. Ad Campaign: create -> read -> update -> delete.
3. Sprawdzenie walidacji pól wymaganych i komunikatów błędów.
4. Weryfikacja zapisu danych i odświeżenia list.

### Wynik
- Status: **PASS**
- Uwagi: `ad campaign` potwierdzone (create/view/cleanup danych testowych, render w dashboardzie) oraz `affiliate product CRUD` potwierdzone end-to-end (`CREATE/UPDATE/DELETE`).
- Dowód techniczny: wynik skryptu `p1-affiliate-crud-smoke.ps1` (`AFF_CREATE_PASS`, `AFF_UPDATE_PASS`, `AFF_DELETE_PASS`, `SCRIPT_EXIT=0`) + cleanup danych testowych (`remaining=0`).

### Notatka diagnostyczna (affiliate CRUD)
- Wcześniejszy status `BLOCKED` wynikał z użycia niewłaściwej tabeli w skrypcie QA (`wp_affiliate_products`).
- Właściwa tabela modułu to `wp_poradnik_affiliate_products` i ma oczekiwany schemat (`slug`, `affiliate_url`, `category_id`, `status`, `created_at`).

---

## Szablon aktualizacji po teście
- Data/godzina:
- Tester:
- Wynik: PASS / FAIL
- Defekt ID (jeśli dotyczy):
- Krótkie podsumowanie:
