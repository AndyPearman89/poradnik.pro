# PORADNIK.PRO — P1 Runbook + Rollback

Data: 2026-03-13  
Zakres: `P1-07`  
Owner: DevOps

## Status dokumentu
- Stan: **DONE**
- Cel: gotowy plan wdrożenia i wycofania zmian.
- Powiązane komendy testowe: `PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md`
- Powiązana checklista: `PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md`

---

## Runbook (production)

### 1) Pre-deploy
- Zamrożenie okna wdrożeniowego (`change freeze`) i potwierdzenie ownerów dyżuru.
- Backup DB + plików aplikacji z timestampem i identyfikatorem release.
- Potwierdzenie PASS dla P1-01..P1-06 (raporty smoke/E2E) oraz brak blockerów P0/P1.
- Weryfikacja dostępu operacyjnego: WP Admin, DB, logi HTTP/PHP, możliwość szybkiego rollbacku.
- Przygotowanie notatki release: zakres zmian, ryzyka, kryteria Go/No-Go.

### 2) Deploy
- Wdrożenie artefaktów kodu (`mu-plugins`, `themes`, konfiguracje) zgodnie z planem release.
- Uruchomienie migracji DB i potwierdzenie braku błędów krytycznych.
- Flush rewrite/cache (jeśli wymagane) oraz kontrola integralności endpointów REST.
- Smoke po wdrożeniu:
	- `GET /wp-admin/tools.php` (dostęp admin),
	- `POST /wp-json/poradnik/v1/affiliate/click` (walidacja zapisu),
	- dashboard kampanii (`/wp-json/poradnik/v1/api/campaigns`) dla konta reklamodawcy,
	- sponsored workflow na poziomie minimalnym (submit + transition).

### 3) Post-deploy
- Monitoring 24h: błędy HTTP 5xx, błędy PHP, metryki klików i eventów monetyzacyjnych.
- Monitoring 7 dni: trend revenue/CTR, stabilność endpointów oraz liczba błędów walidacji.
- Raport dzienny (D+1..D+7): status, incydenty, decyzje o hotfix/rollback.

---

## Rollback (production)

### Trigger rollback
- Krytyczne błędy 500.
- Niepowodzenie płatności lub trackingu.
- Regresja funkcji admin krytycznych.
- Utrata integralności danych po migracji (potwierdzona przez ownera backend).

### Kroki rollback
1. Decyzja `No-Go` i komunikat incydentowy do ownerów (`DevOps`, `Backend`, `QA`, `PO`).
2. Zatrzymanie ruchu wdrożeniowego / wycofanie aktywnego release artifact.
3. Przywrócenie plików aplikacji z ostatniego stabilnego backupu.
4. Odtworzenie bazy danych do snapshotu sprzed release (jeśli dotyczy zmian DB).
5. Flush cache/rewrite i restart usług zależnych (jeśli wymagane środowiskowo).
6. Re-test krytyczny po rollbacku:
	 - logowanie admin + Tools,
	 - endpointy prywatne/publiczne REST,
	 - sponsored + affiliate tracking ścieżki krytyczne.
7. Potwierdzenie stabilizacji i zamknięcie incydentu z raportem RCA.

### Maksymalny czas decyzji rollback
- `TTR (time-to-rollback decision)`: do 15 minut od wykrycia krytycznego defektu.
- `TTR (time-to-restore)`: do 60 minut dla pełnego powrotu do stabilnej wersji.

---

## Checklista odbioru runbooka
- [x] Procedura wdrożenia opisana krok po kroku
- [x] Procedura rollback opisana krok po kroku
- [x] Kryteria triggerów rollback potwierdzone
- [x] Właściciele i kontakty eskalacyjne uzupełnione

---

## Matryca odpowiedzialności (RACI-lite)
- DevOps: wykonanie deploy/rollback
- QA Lead: smoke + akceptacja funkcjonalna
- Backend Lead: walidacja endpointów i logów API
- Product Owner: decyzja Go/No-Go
- Incident Commander (rotacyjnie): koordynacja komunikacji i timeline incydentu

## Decyzja Go/No-Go
- [ ] P1-01..P1-06 mają wynik PASS lub zaakceptowany workaround
- [ ] Brak blockerów krytycznych (P0/P1)
- [ ] Backup i plan rollback zweryfikowane
- [ ] Monitoring 24h przygotowany
