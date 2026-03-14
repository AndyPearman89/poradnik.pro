# PORADNIK.PRO — P1 REST Auth & Payload Validation Report

Data: 2026-03-13  
Zakres: `P1-04`, `P1-05`  
Owner: Backend Lead

## Status ogólny
- Stan raportu: **PASS (część P1-04/P1-05)**
- Środowisko: local
- Cel: potwierdzić ochronę endpointów prywatnych i walidację payloadu endpointów publicznych.
- Playbook komend: `PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md`

---

## P1-04 — REST: endpointy prywatne odrzucają brak autoryzacji

### Kroki testowe
1. Wywołanie endpointów prywatnych bez tokena/sesji.
2. Wywołanie z nieprawidłowymi uprawnieniami.
3. Weryfikacja kodów odpowiedzi (`401/403`) i komunikatów błędów.

### Wynik
- Status: **PASS**
- Uwagi: endpoint prywatny bez autoryzacji został odrzucony.
- Dowód techniczny: `GET /wp-json/poradnik/v1/dashboard/overview -> 401` (bez auth).

---

## P1-05 — REST: endpointy publiczne odrzucają niepoprawny payload

### Kroki testowe
1. Wywołanie public endpointów z brakującymi polami wymaganymi.
2. Wywołanie z błędnym typem danych (np. string zamiast int).
3. Wywołanie z payloadem poza zakresem walidacji.
4. Weryfikacja kodów odpowiedzi (`400`) i treści walidacji.

### Wynik
- Status: **PASS**
- Uwagi: endpoint publiczny odrzuca niepoprawny payload.
- Dowód techniczny: `POST /wp-json/poradnik/v1/affiliate/click` (invalid payload) -> `400`.

---

## Szablon aktualizacji po teście
- Data/godzina:
- Tester:
- Wynik: PASS / FAIL
- Defekt ID (jeśli dotyczy):
- Krótkie podsumowanie:

## Rejestr wykonania

| Data/godzina | Endpoint | Typ testu | Oczekiwany status | Wynik status | PASS/FAIL | Dowód |
|---|---|---|---|---|---|---|
| 2026-03-13 01:22 | `/wp-json/poradnik/v1/dashboard/overview` | brak autoryzacji | 401/403 | 401 | PASS | `Invoke-WebRequest GET bez auth` |
| 2026-03-13 01:21 | `/wp-json/poradnik/v1/affiliate/click` | invalid payload | 400 | 400 | PASS | `Invoke-WebRequest POST invalid payload` |
