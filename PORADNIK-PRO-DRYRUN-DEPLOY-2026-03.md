# PORADNIK.PRO — Suchy przebieg deployu (Dry-Run)

Data: 2026-03-13  
Owner: DevOps / Platform Lead  
Status: **PRODUKCYJNA**  
Cel: Walidacja procedury wdrożenia bez żadnych zmian biznesowych na środowisku local/stage.

---

## Cel i zasady dry-run

Dry-run to **pełne odegranie procedury deploy** z runbooka, ale bez wprowadzania zmian funkcjonalnych.  
Weryfikuje poprawność kroków operacyjnych, narzędzi i odpowiedzialności, zanim zostanie wykonany prawdziwy deploy.

**Zasady:**
- Wykonujemy na środowisku `local` (poradnikpro.local) lub `stage` — **nigdy na production**.
- Wszelkie zmiany kodu/DB w dry-run muszą być identycznie odwracalne (cleanup obowiązkowy).
- Każdy krok jest odhaczany i sygnowany przez wykonującego.
- Wynik końcowy: `DRY_RUN_PASS` lub `DRY_RUN_FAIL` z listą problemów do naprawienia przed prawdziwym go-live.

---

## Faza 0 — Przygotowanie dry-run

| # | Krok | Wykonawca | Wynik |
|---|------|-----------|-------|
| 0.1 | Potwierdzenie środowiska: `local` lub `stage`, nie `production` | DevOps | `[ ]` |
| 0.2 | Snapshot DB przed dry-run: `mysqldump -u root -p local > dry_run_backup_$(date +%Y%m%d_%H%M%S).sql` | DevOps | `[ ]` |
| 0.3 | Zapis baseline plików: suma `md5sum` kluczowych plików `mu-plugins/platform-core/` | DevOps | `[ ]` |
| 0.4 | Potwierdzenie dostępu do WP Admin, do DB i do logów HTTP/PHP | DevOps | `[ ]` |
| 0.5 | Sprawdzenie, że skrypty smoke (`p1-affiliate-crud-smoke.ps1`, `p1-sponsored-smoke.ps1`, `p1-affiliate-tracking-e2e.ps1`) są gotowe do uruchomienia | QA Lead | `[ ]` |

---

## Faza 1 — Pre-deploy (symulacja)

| # | Krok | Wykonawca | Oczekiwany wynik | Wynik |
|---|------|-----------|-----------------|-------|
| 1.1 | Ogłoszenie okna wdrożeniowego w kanale `#ops` (choćby wiadomość testowa) | DevOps | Wiadomość wysłana | `[ ]` |
| 1.2 | Weryfikacja listy P1-01..P1-06: wszystkie statusy DONE | QA Lead | Brak BLOCKED/TODO | `[ ]` |
| 1.3 | Weryfikacja braku blockerów P0/P1 w checklist execution | Product Owner | 0 otwartych blokerów | `[ ]` |
| 1.4 | Potwierdzenie backupu z 0.2 — czy plik `.sql` istnieje i ma rozmiar > 0 | DevOps | `Test-Path backup.sql` = `True` | `[ ]` |
| 1.5 | Sprawdzenie Decyzja Go/No-Go z runbooka — wszystkie warunki spełnione | Platform Lead | Wszystkie `[x]` | `[ ]` |

---

## Faza 2 — Deploy (symulacja — bez faktycznych zmian kodu)

Zamiast wdrażać nowy kod, symulujemy procedurę z runbooka na bieżącym kodzie (re-deploy identycznej wersji).

| # | Krok | Komenda / Akcja | Wykonawca | Oczekiwany wynik | Wynik |
|---|------|-----------------|-----------|-----------------|-------|
| 2.1 | Symulacja deploy artefaktów: skopiowanie `mu-plugins/platform-core/` do katalogu testowego | DevOps | Kopia w `/tmp/dry-run-artifacts/` | `[ ]` |
| 2.2 | Uruchomienie migracji DB (ponowne — bez zmian schematu, idempotentne) | DevOps | `Migrator::run()` — brak `Fatal error`, exit 0 | `[ ]` |
| 2.3 | Flush rewrite rules: `wp rewrite flush` | DevOps | `Success: Rewrite rules flushed.` | `[ ]` |
| 2.4 | Weryfikacja integralności endpointów REST po flush: `GET /wp-json/poradnik/v1/` | DevOps | HTTP 200, `namespace` zawiera `poradnik/v1` | `[ ]` |
| 2.5 | Weryfikacja dostępu admina: `GET /wp-admin/` | DevOps | HTTP 200 lub redirect do logowania (poprawny) | `[ ]` |

**Komenda weryfikacji REST (PowerShell):**
```powershell
$r = Invoke-WebRequest "http://poradnikpro.local/wp-json/poradnik/v1/" -ErrorAction Stop
Write-Output ("REST_NAMESPACE_STATUS=" + $r.StatusCode)
```

**Komenda flush rewrite (WP-CLI przez MySQL fallback):**
```powershell
# Jeśli WP-CLI niedostępne lokalnie — wymuszamy reload modrewrite:
$r = Invoke-WebRequest "http://poradnikpro.local/wp-admin/options-permalink.php" `
     -Method POST -WebSession $session -Body @{permalink_structure='/%-postname-%/'; _wpnonce=$nonce}
```

---

## Faza 3 — Smoke po deployu (obowiązkowy)

Uruchomić wszystkie 3 skrypty QA sekwencyjnie. Wszystkie muszą zwrócić `SCRIPT_EXIT=0`.

| # | Skrypt | Komenda | Oczekiwany wynik |
|---|--------|---------|-----------------|
| S-01 | Admin login + Tools | `.\p1-affiliate-crud-smoke.ps1` | `AFF_CREATE_PASS`, `AFF_UPDATE_PASS`, `AFF_DELETE_PASS`, `SCRIPT_EXIT=0` |
| S-02 | Sponsored workflow | `.\p1-sponsored-smoke.ps1` | `SP_SUBMIT_PASS`, `SP_REVIEW_PASS`, `SP_PAID_PASS`, `SP_PUBLISH_PASS`, `SP_CLEANUP_PASS`, `SCRIPT_EXIT=0` |
| S-03 | Affiliate tracking E2E | `.\p1-affiliate-tracking-e2e.ps1` | `AFTR_API_PASS`, `AFTR_DB_VERIFY_PASS`, `AFTR_CLEANUP_PASS`, `AFTR_SCRIPT_EXIT=0` |

**Wynik dry-run:**
- Wszystkie `PASS` → `DRY_RUN_SMOKE=PASS`
- Jakikolwiek FAIL → `DRY_RUN_SMOKE=FAIL` — **stop, nie kontynuować go-live**

---

## Faza 4 — Walidacja monitoringu i sond

| # | Krok | Komenda / Akcja | Oczekiwany wynik | Wynik |
|---|------|-----------------|-----------------|-------|
| 4.1 | Test PROBE-01: dashboard stats | `GET /wp-json/poradnik/v1/dashboard/stats` (z auth) | HTTP 200, pole `data` obecne | `[ ]` |
| 4.2 | Test PROBE-02: affiliate click (test payload) | `POST /wp-json/poradnik/v1/affiliate/click` | HTTP 200, `click_id` > 0 | `[ ]` |
| 4.3 | Test PROBE-03: admin uptime | `GET /wp-admin/` | HTTP 200 | `[ ]` |
| 4.4 | Weryfikacja logów PHP: brak `Fatal error` / `Uncaught` w `debug.log` z ostatnich 5 minut | DevOps | 0 wpisów Fatal/Uncaught | `[ ]` |
| 4.5 | Sprawdzenie czy alerty Slack `#ops-alerts` odbierają zdarzenia testowe (jeśli bot skonfigurowany) | DevOps | Powiadomienie testowe dostarczone lub `N/A-local` | `[ ]` |

---

## Faza 5 — Cleanup i raport dry-run

| # | Krok | Wykonawca | Wynik |
|---|------|-----------|-------|
| 5.1 | Usunięcie katalogu testowego `/tmp/dry-run-artifacts/` | DevOps | `[ ]` |
| 5.2 | Weryfikacja, że DB nie zawiera danych testowych z fazy 2 (idempotentne migracje nie dodały duplikatów) | DevOps | `[ ]` |
| 5.3 | Wpisanie wyniku dry-run do dziennika execution checklist | DevOps/QA | `[ ]` |
| 5.4 | Decyzja: `GO` (wszystko PASS) lub `NO-GO` (lista problemów) | Platform Lead | `[ ]` |

---

## Wynik dry-run (wypełnić po wykonaniu)

| Pole | Wartość |
|------|---------|
| Data wykonania | *(wypełnić)* |
| Środowisko | `local` / `stage` |
| Wykonawca | *(wypełnić)* |
| Wynik fazy 1 | `PASS` / `FAIL` |
| Wynik fazy 2 | `PASS` / `FAIL` |
| `DRY_RUN_SMOKE` | `PASS` / `FAIL` |
| Wynik fazy 4 | `PASS` / `FAIL` |
| Wynik końcowy | `DRY_RUN_PASS` / `DRY_RUN_FAIL` |
| Lista problemów | *(jeśli FAIL — opisać)* |
| Decyzja | `GO` / `NO-GO` |

---

## Linki do artefaktów

- [Runbook + Rollback](PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md)
- [Go-Live Checklist](PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md)
- [Matryca ryzyk](PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md)
- [Sprint 12 Plan](PORADNIK-PRO-SPRINT-12-PLAN-2026-03-13.md)
- Skrypty smoke: `themes/poradnik-theme/scripts/p1-*.ps1`
