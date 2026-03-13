# PORADNIK.PRO — CHECKLISTA WYKONAWCZA (P1/P2/P3)

Data: 13 marca 2026  
Źródło: `PORADNIK-PRO-IMPLEMENTATION-CHECKLIST.md`

## Cel
Uproszczona lista zadań do dowiezienia produkcyjnego uruchomienia, bez sekcji strategicznych i bez duplikatów.

## Snapshot
- Foundation / Content Model / DB / Affiliate / Ads / Stripe / SEO automation: **zrobione**
- Największe luki: **QA/E2E/Go-Live**, **Analytics**, **Performance**, **AI**, **Multilingual**

## Sprint bieżący (tydzień 11 / marzec 2026)
Właściciel: zespół platformy  
Cel sprintu: zamknąć krytyczne elementy P1 przed go-live.

### Zadania sprintu
- [x] Smoke: logowanie admin + dostęp do Tools
- [x] Smoke: CRUD affiliate product i CRUD ad campaign *(ad campaign: PASS; affiliate CRUD: PASS)*
- [x] Smoke: sponsored workflow (submit -> review -> paid -> publish)
- [x] REST: prywatne endpointy odrzucają brak autoryzacji
- [x] REST: publiczne endpointy odrzucają niepoprawny payload
- [x] E2E: sponsored workflow + ad campaign workflow + affiliate tracking
- [x] Runbook wdrożeniowy + rollback plan

### Kryteria sprintu
- [ ] 0 błędów krytycznych w smoke i E2E
- [ ] Wszystkie testy P1 udokumentowane (data, wynik, osoba)
- [ ] Gotowość do wejścia w checklistę Go-Live

---

## P1 — Blokery go-live (najpierw)

### 1) QA + Release Readiness
- [x] Smoke test: logowanie admin + dostęp do Tools
- [x] Smoke test: CRUD affiliate product
- [x] Smoke test: CRUD ad campaign
- [x] Smoke test: sponsored workflow (submit -> review -> paid -> publish)
- [ ] Smoke test: AI Assistant + AI Image (bez 500)
- [ ] Smoke test: Programmatic SEO builder (draft generation)
- [x] REST test: endpointy prywatne odrzucają brak autoryzacji
- [x] REST test: endpointy publiczne odrzucają niepoprawny payload
- [x] Lint: `php -l` dla `mu-plugins/platform-core/**` bez błędów
- [x] E2E: sponsored workflow
- [x] E2E: ad campaign workflow
- [x] E2E: affiliate tracking
- [ ] Runbook wdrożeniowy + rollback plan

### 2) Go-Live Checklist
- [ ] Pełny backup przed deploy
- [ ] Wdrożenie migracji DB
- [ ] Weryfikacja webhooks Stripe
- [ ] Weryfikacja tagów Analytics/GSC
- [ ] Weryfikacja SEO (schema + sitemap + hreflang)
- [ ] Monitoring 24h po deploy
- [ ] Monitoring 7 dni (errors, revenue, CTR)

### 3) Kryteria zamknięcia P1
- [ ] Sekcja QA oznaczona jako done
- [ ] Sekcja Go-Live oznaczona jako done
- [ ] Brak regresji krytycznych w modułach płatności i reklam

---

## P2 — Stabilność ruchu i monetyzacji

### 4) Analytics + Measurement
- [ ] Integracja Google Analytics
- [ ] Integracja Google Search Console
- [ ] Eventy: `affiliate_click`, `ad_click`, `ad_impression`
- [ ] Dashboard KPI: sessions / CTR / revenue per type
- [ ] Raporty tygodniowe i miesięczne

### 5) Performance + Scale (minimum produkcyjne)
- [ ] Lazy loading obrazów
- [ ] Kompatybilność z cache (page/object)
- [ ] CDN readiness
- [ ] Optymalizacja zapytań DB i indeksów
- [ ] Kolejkowanie zadań ciężkich (AI/Programmatic)

### 6) Security domknięcie
- [ ] Audyt logów bezpieczeństwa

### 7) Kryteria zamknięcia P2
- [ ] Dane KPI dostępne codziennie
- [ ] Brak krytycznych regresji wydajności po wdrożeniu

---

## P3 — Skalowanie treści

### 8) Guide Generator
- [ ] Struktura guide + brakujące pola ACF + repeater steps
- [ ] Frontend generator: `/generator-poradnikow`
- [ ] Prompt template + wersjonowanie promptów
- [ ] Programmatic generator dla fraz `Jak [czynność] [X]`
- [ ] Batch generation (100/1000/10000) z limitami i kolejką
- [ ] Template guide + schema HowTo + interactive elements
- [ ] CTA i internal linking (`guide -> ranking/review/tools`)
- [ ] Mechanizm skalowania pod tysiące słów kluczowych

### 9) AI Content + AI Image
- [ ] Headline / Outline / FAQ / Meta description / Ranking copy
- [ ] Panel AI Article Assistant (admin)
- [ ] Guardrails jakości (jakość, długość, banned claims)
- [ ] Generator obrazów + formaty OG/hero/social
- [ ] Auto-upload do Media Library + auto-podpięcie featured/OG

### 10) Multilingual SEO
- [ ] Konfiguracja języków: PL/EN/DE/ES/FR
- [ ] URL strategy: `/pl/`, `/en/`, `/de/`, `/es/`, `/fr/`
- [ ] Language switcher frontend
- [ ] Hreflang mapowanie między wersjami
- [ ] Lokalizacja schema i metadanych

### 11) Category Map i klastery (po stabilizacji)
- [ ] Walidacja mapy kategorii (unikalność slugów, brak pustych podkategorii)
- [ ] Programmatic SEO patterns (`jak zrobić`, `jak ustawić`, itd.)
- [ ] Klastrowanie: `guide -> ranking -> review -> comparison`
- [ ] SEO site map i skala publikacji (etapowo)

---

## Niespójności do uprzątnięcia w dokumencie źródłowym
- [ ] Usunąć duplikat sekcji `1) Content Model` (jest powtórzona)
- [ ] Ujednolicić status sekcji 18 (REST done vs smoke not done)
- [ ] Oddzielić część „Execution” od części „Strategia/Skala”
- [x] Zweryfikować zgodność tabel QA ze źródłem danych modułu (`wp_poradnik_affiliate_products`)

## Proponowana kolejność realizacji (operacyjna)
1. P1 (blokery go-live)  
2. P2 (pomiar + wydajność + bezpieczeństwo operacyjne)  
3. P3 (AI + multilingual + skala programmatic)

---

## Dziennik wykonania (P1/P2/P3)

Playbook komend testowych: `PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md`

| Data | Priorytet | Obszar | Zadanie / Test | Wynik | Owner | Uwagi |
|---|---|---|---|---|---|---|
| 2026-03-13 | P1 | QA | Lint `php -l` dla `mu-plugins/platform-core/**` | PASS | platform-team | Bez błędów składni |
| 2026-03-13 | P1 | REST | Testy endpointów auth + validation | PASS | platform-team | Potwierdzone w checklist source |
| 2026-03-13 | P1 | Dokumentacja | Utworzenie checklisty execution P1/P2/P3 | PASS | platform-team | Single source of truth |
| 2026-03-13 | P1 | REST | Brak autoryzacji na endpoint prywatny | PASS | Backend Lead | `GET /dashboard/overview` zwraca `401` |
| 2026-03-13 | P1 | REST | Invalid payload na endpoint publiczny | PASS | Backend Lead | `POST /affiliate/click` zwraca `400` |
| 2026-03-13 | P1 | Dashboard | Routing advertiser dashboard + canonical redirect + multilingual paths | PASS | platform-team | `/advertiser/dashboard` i `/reklamodawca/dashboard/new-campaign` działają, `/dashboard/advertiser` => `301` |
| 2026-03-13 | P1 | Security | Hardening API kampanii (`advertiser_id` scope + ownership checks) | PASS | Backend Lead | Zmiany w `platform-core/Api/Controllers/DashboardController.php` |
| 2026-03-13 | P1 | QA | Ad campaign smoke (konto `REKLAMAPRO`) — create/view/cleanup danych testowych | PASS | QA Lead | Potwierdzony zapis i render kampanii, następnie cleanup danych testowych |
| 2026-03-13 | P1 | E2E | Ad campaign workflow (routing + create/view + cleanup) | PASS | QA Automation | Część ad campaign potwierdzona; sponsored i affiliate tracking nadal w toku |
| 2026-03-13 | P1 | QA | Admin login + dostęp do Tools (`/wp-admin/tools.php`) | PASS | QA Lead | Konto techniczne `QAADMIN`, wynik smoke: `TOOLS_ACCESS=PASS` |
| 2026-03-13 | P1 | QA | Affiliate product CRUD smoke | PASS | QA Lead | CREATE/UPDATE/DELETE potwierdzone po korekcie skryptu QA na właściwą tabelę `wp_poradnik_affiliate_products`; `SCRIPT_EXIT=0` |
| 2026-03-13 | P1 | QA | Sponsored workflow smoke (submit -> review -> paid -> publish) | PASS | QA Lead | `p1-sponsored-smoke.ps1`: `SP_SUBMIT_PASS`, `SP_REVIEW_PASS`, `SP_PAID_PASS`, `SP_PUBLISH_PASS`, `SP_CLEANUP_PASS`, `SCRIPT_EXIT=0` |
| 2026-03-13 | P1 | E2E | Affiliate tracking (click -> persist -> verify) | PASS | QA Automation | `p1-affiliate-tracking-e2e.ps1`: `AFTR_API_PASS`, DB verify `wp_poradnik_affiliate_clicks`, cleanup danych, `AFTR_SCRIPT_EXIT=0` |
| 2026-03-13 | P1 | DevOps | Runbook wdrożeniowy + rollback plan (wersja produkcyjna) | PASS | DevOps | Zaktualizowano `PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md` i dodano `PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md` |
| 2026-03-13 | P1 | DevOps | Matryca ryzyk go-live (progi alertów, sondy, eskalacja) | PASS | DevOps | Utworzono `PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md`: 12 metryk, 9 ryzyk, 7 sond, 5-poziomowa eskalacja |
| 2026-03-13 | P1 | DevOps | Suchy przebieg deployu (dry-run procedure) | PASS | DevOps | Utworzono `PORADNIK-PRO-DRYRUN-DEPLOY-2026-03.md`: 5 faz, 25 kroków, tabela wyników, cleanup, integracja z 3 skryptami smoke |
| 2026-03-13 | P2 | Bugfix | Bug `Tracker.php`: `ad_impression` INSERT fail — brak kolumny `user_ip` | PASS | Backend Lead | Naprawiono `platform-core/Domain/Ads/Tracker.php`: dodano `bool $trackIp` do `track()`; `trackImpression` wywołuje z `false` |
| 2026-03-13 | P2 | Measurement | Walidacja eventów monetyzacyjnych (affiliate_click, ad_click, ad_impression) | PASS | QA Automation | `p1-measurement-events-e2e.ps1`: `MEAS_AFF_DB_PASS`, `MEAS_ADCLICK_DB_PASS`, `MEAS_ADIMPR_DB_PASS`, `MEAS_SCRIPT_EXIT=0`; baseline doc utworzony |
| 2026-03-13 | P2 | Performance | Baseline TTFB + top zapytania DB + quick wins | PASS | DevOps | `p1-performance-baseline.ps1`: 4 endpointy (20 prób), `PERF_SCRIPT_EXIT=0`; utworzono `PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md` |
| 2026-03-13 | P2 | Measurement | Raport kontrolny dzienny: wolumen eventów + błędy zapisu | PASS | QA Automation | `p1-measurement-daily-report.ps1`: wygenerowano `PORADNIK-PRO-MEASUREMENT-DAILY-REPORT-2026-03-13.md`, `MEAS_DAILY_REPORT_PASS` |

### Szablon nowego wpisu
`| YYYY-MM-DD | P1/P2/P3 | Obszar | Zadanie / Test | PASS/FAIL | Owner | Krótka notatka |`

---

## P1 — Najbliższe 7 zadań (owner + termin)

| ID | Zadanie | Priorytet | Owner | Termin | Status | Dowód |
|---|---|---|---|---|---|---|
| P1-01 | Smoke: logowanie admin + dostęp do Tools | P1 | QA Lead | 2026-03-14 | DONE | `PORADNIK-PRO-P1-SMOKE-REPORT-2026-03-13.md` |
| P1-02 | Smoke: CRUD affiliate product i CRUD ad campaign | P1 | QA Lead | 2026-03-14 | DONE | `PORADNIK-PRO-P1-SMOKE-REPORT-2026-03-13.md` (ad campaign: PASS; affiliate CRUD: PASS) |
| P1-03 | Smoke: sponsored workflow (submit -> review -> paid -> publish) | P1 | QA Lead | 2026-03-15 | DONE | `PORADNIK-PRO-P1-SPONSORED-SMOKE-REPORT-2026-03-13.md` |
| P1-04 | REST: prywatne endpointy odrzucają brak autoryzacji | P1 | Backend Lead | 2026-03-15 | DONE | `PORADNIK-PRO-P1-REST-AUTH-VALIDATION-REPORT-2026-03-13.md` |
| P1-05 | REST: publiczne endpointy odrzucają niepoprawny payload | P1 | Backend Lead | 2026-03-15 | DONE | `PORADNIK-PRO-P1-REST-AUTH-VALIDATION-REPORT-2026-03-13.md` |
| P1-06 | E2E: sponsored workflow + ad campaign workflow + affiliate tracking | P1 | QA Automation | 2026-03-16 | DONE | `PORADNIK-PRO-P1-E2E-REPORT-2026-03-13.md` (sponsored + ad campaign + affiliate tracking: PASS) |
| P1-07 | Runbook wdrożeniowy + rollback plan | P1 | DevOps | 2026-03-16 | DONE | `PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md` + `PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md` |

### Zasada aktualizacji statusu
- `TODO` -> `IN PROGRESS` -> `DONE` / `BLOCKED`

### Dowód wykonania (format)
- Dla testów: link do logu/raportu (`/wp-content/mu-plugins/logs/...` albo plik markdown z wynikami).
- Dla dokumentacji: link do pliku docelowego.
- W kolumnie `Dowód` wstawiamy ścieżkę lub identyfikator artefaktu.
