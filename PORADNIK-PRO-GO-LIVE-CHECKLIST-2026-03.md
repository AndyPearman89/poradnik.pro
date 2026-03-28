# PORADNIK.PRO — Go-Live Checklist (Marzec 2026)

Data: 2026-03-13  
Owner: DevOps / Platform Team

## Status operacyjny (aktualizacja: 2026-03-18)
- Decyzja produkcyjna: **PENDING (NO-GO do czasu zamknięcia blokerów operacyjnych).**
- Blokery operacyjne:
  - [ ] Potwierdzenie backup + test restore wykonane na środowisku produkcyjnym (nie tylko local).
  - [ ] Formalne domknięcie raportów P1-01..P1-06 ze statusem końcowym `DONE`.
  - [ ] Uzupełnienie D+1 baseline wolumenów (`affiliate_click`, `ad_click`, `ad_impression`, CTR).
- Potwierdzone dowody techniczne (na dziś):
  - [x] `P1-03` sponsored smoke — PASS.
  - [x] `P1-04` REST auth — PASS.
  - [x] `P1-05` payload validation — PASS.
  - [x] `P1-06` E2E (sponsored/ad campaign/affiliate tracking) — PASS.
  - [x] Smoke gate komenda produkcyjna zdefiniowana (`tools/rest-smoke.ps1`, `SMOKE_FAILED=0`).

## Artefakty powiązane
- [Runbook + Rollback](PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md)
- [Matryca ryzyk go-live](PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md) — progi alertów, sondy, eskalacja
- [Sprint 12 Plan](PORADNIK-PRO-SPRINT-12-PLAN-2026-03-13.md)

## A. Przed wdrożeniem
- [ ] Backup DB wykonany i zweryfikowany (snapshot + test restore metadata).
- [ ] Backup plików aplikacji wykonany (`mu-plugins`, `themes`, konfiguracje).
- [ ] Potwierdzone PASS dla P1-01..P1-06 oraz status raportów ustawiony na `DONE`.
- [ ] Uruchomiony smoke gate pre-deploy:
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\rest-smoke.ps1 -BaseUrl https://poradnik.pro -Strict`
  - [ ] Wynik: `SMOKE_FAILED=0`
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro`
  - [ ] Wynik: `PRODUCTION_GATE=PASS`
- [ ] Brak aktywnych blockerów P0/P1.
- [ ] Kanał eskalacji i ownerzy dyżuru potwierdzeni.

## B. Wdrożenie
- [ ] Release artefakt wdrożony na target environment.
- [ ] Migracje DB wykonane bez błędów krytycznych.
- [ ] Rewrite/cache odświeżone (jeśli wymagane).
- [ ] Smoke krytyczny po deployu zakończony PASS:
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\rest-smoke.ps1 -BaseUrl https://poradnik.pro -Strict`
  - [ ] Wynik: `SMOKE_FAILED=0`
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro`
  - [ ] Wynik: `PRODUCTION_GATE=PASS`
  - [ ] Admin Tools
  - [ ] Sponsored workflow (minimum)
  - [ ] Potwierdzono statusy endpointów wg aktywnego namespace (`poradnik/v1` lub `peartree/v1`)

## C. Po wdrożeniu (24h)
- [ ] Monitoring 5xx/4xx i błędów PHP aktywny.
- [ ] Monitoring eventów monetyzacyjnych aktywny (`affiliate_click`, `ad_click`, `ad_impression`).
- [ ] Brak anomalii krytycznych revenue/CTR.
- [ ] Raport D+1 przygotowany i zatwierdzony (w tym baseline godzinowy i CTR).

## D. Stabilizacja (7 dni)
- [ ] Raporty D+2..D+7 dostępne.
- [ ] Brak regresji krytycznych funkcji platformy.
- [ ] Decyzja o zamknięciu okresu go-live podpisana przez ownerów.

## E. Rollback readiness
- [ ] Trigger rollback i SLA decyzji znane (`<=15 min`).
- [ ] Procedura restore technicznie przetestowana.
- [ ] Krytyczne testy po rollbacku zdefiniowane i gotowe do uruchomienia.
- [ ] Komenda re-test po rollbacku gotowa:
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\rest-smoke.ps1 -BaseUrl https://poradnik.pro -Strict`
  - [ ] Kryterium akceptacji: `SMOKE_FAILED=0`
  - [ ] `PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro`
  - [ ] Kryterium akceptacji: `PRODUCTION_GATE=PASS`
