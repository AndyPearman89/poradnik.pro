# PORADNIK.PRO — Matryca ryzyk Go-Live (Marzec 2026)

Data: 2026-03-13  
Owner: DevOps / Platform Team  
Status: **PRODUKCYJNA**

---

## 1. Progi alertów i metryki monitoringu

| # | Metryka | Próg WARN | Próg CRIT | Okno pomiaru | Owner | Akcja WARN | Akcja CRIT |
|---|---------|-----------|-----------|--------------|-------|------------|------------|
| M-01 | HTTP 5xx rate | > 1 % req/min | > 5 % req/min | 5 min rolling | DevOps | Alert Slack #ops | Eskalacja L1 → incident |
| M-02 | HTTP 4xx rate (płatne endpointy) | > 5 % req/min | > 15 % req/min | 5 min rolling | Platform | Sprawdź logi PHP | Alert PM + DevOps |
| M-03 | TTFB `/wp-json/poradnik/v1/dashboard/*` | > 800 ms p95 | > 2 000 ms p95 | 15 min rolling | Platform | Profil zapytań DB | Cache purge + escalate |
| M-04 | TTFB `/wp-json/poradnik/v1/affiliate/click` | > 500 ms p95 | > 1 500 ms p95 | 15 min rolling | Platform | Sprawdź indeksy | Rollback kandidat |
| M-05 | Wolumen `affiliate_click` / godz. | < 50 % baseline | < 20 % baseline | 60 min rolling | PM / Analityka | Sprawdź tracking JS | Alert PM + weryfikacja |
| M-06 | Wolumen `ad_click` / godz. | < 50 % baseline | < 20 % baseline | 60 min rolling | PM / Analityka | Sprawdź tracking JS | Alert PM + weryfikacja |
| M-07 | Wolumen `ad_impression` / godz. | < 50 % baseline | < 20 % baseline | 60 min rolling | PM / Analityka | Sprawdź tracking JS | Alert PM + weryfikacja |
| M-08 | Błędy PHP (`error_log` WARNING+) | > 10 / min | > 50 / min | 5 min rolling | DevOps | Przejrzyj debug.log | Incident + escalacja |
| M-09 | Czas odpowiedzi sponsored workflow (submit→paid) | > 3 s | > 10 s | sample 10 %  | Platform | DB profiling | Escalate + freeze |
| M-10 | DB slow queries (> 1 s) | > 5 / min | > 20 / min | 5 min rolling | DevOps | Sprawdź indeksy | Rollback kandidat |
| M-11 | Rozmiar kolejki WP Cron zaległych | > 50 tasks | > 200 tasks | 30 min rolling | Platform | Sprawdź Cron runner | Restart Cron + alert |
| M-12 | Dostępność admina (`/wp-admin/`) uptime | < 99,5 % | < 99 % | 10 min rolling | DevOps | Restart PHP-FPM | Pełny incident plan |

---

## 2. Priorytety ryzyk biznesowych

| ID | Ryzyko | Prawdopybieństwo | Wpływ | Ryzyko łączne | Mitygacja |
|----|--------|-----------------|-------|---------------|-----------|
| R-01 | Sponsored workflow nieosiągalny po deployu | Niskie | Krytyczny | **WYSOKI** | Smoke B.4 obowiązkowy; rollback w ≤ 15 min |
| R-02 | Affiliate click nie zapisuje do DB | Niskie | Wysoki | **WYSOKI** | E2E PASS przed go-live; alert M-05 |
| R-03 | Regresja dashboard/admin po migracji DB | Niskie | Wysoki | **WYSOKI** | Weryfikacja migracji + snapshot DB |
| R-04 | Utrata danych revenue (płatności) | Bardzo niskie | Krytyczny | **WYSOKI** | Backup D-1 + snapshot RDS; test restore |
| R-05 | Drop 4xx/5xx w integracji Stripe webhooks | Niskie | Wysoki | **ŚREDNI** | Webhook retry Stripe; monitoring 5 min |
| R-06 | Wzrost DB slow queries po zmianie schematu | Średnie | Średni | **ŚREDNI** | Performance baseline + alert M-10 |
| R-07 | Cron zaległości (mail, cleanup) | Średnie | Niski | **NISKI** | Alert M-11; manual run Cron jeśli potrzeba |
| R-08 | Tracking JS niezaładowany w buforze CDN | Niskie | Średni | **NISKI** | Cache purge krok B.3 deploy checklist |
| R-09 | Środowisko local vs production drift | Średnie | Niski | **NISKI** | Dry-run A3; checklista środowiskowa |

---

## 3. Sondy alertów (probe schedule)

| Sonda | URL / Komenda | Częstotliwość | Oczekiwany wynik | Alert jeśli |
|-------|---------------|---------------|-----------------|-------------|
| PROBE-01 | `GET /wp-json/poradnik/v1/dashboard/stats` (auth) | 1 min | HTTP 200; `json.data` istnieje | HTTP ≠ 200 lub brak `data` |
| PROBE-02 | `POST /wp-json/poradnik/v1/affiliate/click` (test payload) | 5 min | HTTP 200; `click_id` > 0 | HTTP ≠ 200 lub `click_id` null |
| PROBE-03 | `GET /wp-admin/` (QAADMIN session) | 5 min | HTTP 200 | HTTP ≠ 200, redirect niespodziewany |
| PROBE-04 | DB query: `SELECT COUNT(*) FROM wp_poradnik_affiliate_clicks WHERE created_at > NOW()-INTERVAL 10 MINUTE` | 10 min | > 0 przez +24h po deployu | = 0 przez dwa kolejne okresy |
| PROBE-05 | `SELECT COUNT(*) FROM wp_poradnik_ad_clicks WHERE created_at > NOW()-INTERVAL 60 MINUTE` | 60 min | > 0 | = 0 przez dwa kolejne okresy |
| PROBE-06 | PHP error log tail (500+ lines) | 5 min | Brak `Fatal error` / `Uncaught` | Wykrycie `Fatal`/`Uncaught` |
| PROBE-07 | WP Cron: `wp cron event list --status=due` (WP-CLI) | 30 min | < 50 zaległych | > 50 zaległych |

---

## 4. Matryca eskalacji

| Poziom | Trigger | Kto | Kanał | TTR |
|--------|---------|-----|-------|-----|
| L0 — Auto-heal | M-03/M-04 WARN, M-11 WARN | Bot / Automation | Slack #ops-alerts | ≤ 5 min |
| L1 — DevOps dyżur | Dowolne CRIT M-01..M-04, M-08, M-10, M-12 | DevOps On-Call | Slack #ops-incident + SMS | ≤ 15 min |
| L2 — Platform Lead | L1 nierozwiązane > 15 min; R-01, R-02, R-04 | Platform Lead | Direct + Phone | ≤ 30 min |
| L3 — PM / Biznes | Utrata revenue lub danych; R-04 CRIT | PM + CTO | Phone | ≤ 60 min |
| L4 — Rollback decision | Dowolny trigger rollback z runbooka (5xx CRIT, payment fail, data loss) | Platform Lead + DevOps | War room Slack | ≤ 15 min od decyzji |

---

## 5. Triggery rollbacku (skrót z runbooka)

Pełna procedura: `PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md`

Rollback uruchamiany gdy:
- [ ] HTTP 5xx rate > 5 % przez > 5 min (M-01 CRIT).
- [ ] Sponsored workflow niedostępny — smoke B.4 FAIL.
- [ ] Płatności (Stripe webhook) nie rejestrują > 3 transakcje z rzędu.
- [ ] Utrata / korupcja danych w `wp_poradnik_*`.
- [ ] Platform Lead zatwierdza decyzję.

SLA decyzji: **≤ 15 min od wykrycia**  
SLA restore: **≤ 60 min od decyzji**

---

## 6. Baseline (punkt odniesienia dla anomalii)

> Uzupełnić z wyników `PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md` po zadaniu C.

| Metryka | Baseline (pre-go-live) | Źródło |
|---------|------------------------|--------|
| Wolumen `affiliate_click` / godz. | TBD | Performance Baseline C |
| Wolumen `ad_click` / godz. | TBD | Performance Baseline C |
| TTFB `/dashboard/*` p95 | TBD | Performance Baseline C |
| DB slow queries / min | TBD | Performance Baseline C |

---

## 7. Linki do artefaktów

- [Runbook + Rollback](PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md)
- [Go-Live Checklist](PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md)
- [Sprint 12 Plan](PORADNIK-PRO-SPRINT-12-PLAN-2026-03-13.md)
- [Performance Baseline](PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md) *(do utworzenia — zadanie C)*
