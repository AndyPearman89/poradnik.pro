# PORADNIK.PRO — Sprint 12 Plan

Data utworzenia: 2026-03-13  
Okres: tydzień 12 / marzec 2026  
Owner: platform-team

## Cel sprintu
Przejście z domykania P1 (QA/E2E) do gotowości Go-Live oraz startu P2 (measurement + performance baseline).

## Zakres (Must Have)
1. Domknąć `P1-07`: runbook wdrożeniowy + rollback plan (wersja produkcyjna).
2. Przygotować i zweryfikować checklistę Go-Live (backup, migracje DB, Stripe webhooks, SEO/Analytics).
3. Wdrożyć minimalny pomiar produkcyjny dla eventów: `affiliate_click`, `ad_click`, `ad_impression`.
4. Uruchomić monitoring po-deploy (24h + 7 dni) z prostymi progami alertów.

## Zadania sprintu

### A. Release / Go-Live
- [x] Finalny runbook wdrożeniowy (kroki + owner + ETA + rollback trigger).
- [x] Matryca ryzyk go-live (co monitorujemy, progi, eskalacja). → `PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md`
- [x] Suchy przebieg deployu na local/stage (bez zmian biznesowych). → `PORADNIK-PRO-DRYRUN-DEPLOY-2026-03.md`

### B. Measurement (P2 start)
- [x] Rejestracja eventów do warstwy analitycznej (`affiliate_click`, `ad_click`, `ad_impression`). → `PORADNIK-PRO-MEASUREMENT-BASELINE-2026-03.md`
- [x] Walidacja poprawności payloadów i źródeł danych. → `p1-measurement-events-e2e.ps1`: wszystkie 3 PASS
- [x] Raport kontrolny dzienny: wolumen eventów + błędy zapisu. → `p1-measurement-daily-report.ps1` + `PORADNIK-PRO-MEASUREMENT-DAILY-REPORT-2026-03-13.md`

### C. Performance baseline
- [x] Pomiar baseline TTFB i kluczowych endpointów REST (`dashboard`, `affiliate/click`, `ads/*`). → `p1-performance-baseline.ps1`
- [x] Lista top zapytań DB do optymalizacji (z indeksem priorytetów). → `PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md`
- [x] Plan szybkich usprawnień (quick wins) na kolejny sprint. → `PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md`

## Kryteria Done (Sprint 12)
- [ ] Runbook + rollback zaakceptowane przez ownerów technicznych.
- [ ] Checklista Go-Live gotowa do wykonania bez luk krytycznych.
- [x] Eventy monetyzacyjne raportują się stabilnie i mają dowód walidacji.
- [x] Powstał baseline performance i backlog optymalizacji z priorytetami.

## Artefakty docelowe
- `PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md` (zaktualizowany)
- `PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md` (nowy)
- `PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md` (nowy)
- `PORADNIK-PRO-DRYRUN-DEPLOY-2026-03.md` (nowy)
- `PORADNIK-PRO-MEASUREMENT-BASELINE-2026-03.md` (nowy)
- `PORADNIK-PRO-MEASUREMENT-DAILY-REPORT-2026-03-13.md` (generated)
- `PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md` (nowy)

## Ryzyka
- Brak pełnej telemetrii produkcyjnej może ukryć regresje po deployu.
- Niedomknięte procedury rollback zwiększają ryzyko wydłużonego incidentu.
- Różnice środowisk local/stage/production mogą wpływać na wiarygodność smoke.

## Priorytetyzacja
1. Go-Live safety (`runbook`, `rollback`, checklista)
2. Measurement eventów monetyzacyjnych
3. Performance baseline
