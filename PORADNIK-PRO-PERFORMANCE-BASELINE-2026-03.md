# PORADNIK.PRO — Performance Baseline (Marzec 2026)

Data: 2026-03-13  
Owner: Platform Team / DevOps  
Status: **PRODUKCYJNA**

---

## 1) Zakres baseline

Baseline wykonano dla kluczowych endpointów REST wskazanych w Sprint 12:
- `GET /wp-json/poradnik/v1/dashboard/statistics`
- `POST /wp-json/poradnik/v1/affiliate/click`
- `POST /wp-json/poradnik/v1/ads/click`
- `POST /wp-json/poradnik/v1/ads/impression`

Metodologia:
- Narzędzie: `curl` (`time_starttransfer`)
- Próba: **20 requestów / endpoint**
- Środowisko: local (`poradnikpro.local`)
- Skrypt: `p1-performance-baseline.ps1` (uruchamiany w środowisku WordPress deploy; komenda referencyjna w `PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md`)
- Cleanup danych testowych: wbudowany (`PERF_CLEANUP_PASS`)

---

## 2) Wyniki TTFB (ms)

Wyniki z przebiegu zakończonego `PERF_SCRIPT_EXIT=0`:

| Endpoint | avg (ms) | p50 (ms) | p95 (ms) |
|---|---:|---:|---:|
| `GET /dashboard/statistics` | 65.45 | 61.46 | 89.63 |
| `POST /affiliate/click` | 67.58 | 59.93 | 91.71 |
| `POST /ads/click` | 67.05 | 60.16 | 100.36 |
| `POST /ads/impression` | 64.29 | 57.76 | 94.04 |

Wniosek baseline:
- P95 dla wszystkich mierzonych endpointów mieści się w zakresie **~90–100 ms** na local.
- Najwyższy P95 ma `POST /ads/click` (100.36 ms) — nadal bez sygnału regresji krytycznej.

---

## 3) Wolumen tabel (stan podczas baseline)

| Tabela | Liczba rekordów |
|---|---:|
| `wp_poradnik_ad_campaigns` | 4 |
| `wp_poradnik_ad_clicks` | 6 |
| `wp_poradnik_ad_impressions` | 2 |
| `wp_poradnik_sponsored_articles` | 0 |

Uwaga:
- Obecny wolumen jest niski, więc baseline traktujemy jako **punkt zerowy** do porównań po Go-Live, a nie limit docelowy produkcji.

---

## 4) Top zapytania DB do optymalizacji (priorytet)

Analiza oparta o kod `StatsService.php` + `EXPLAIN`:

### Q1 — kampanie z agregatami kliknięć/wyświetleń (PRIORYTET: WYSOKI)
Źródło: `StatsService::campaigns()`

```sql
SELECT c.id, c.name, c.status, c.budget, c.start_date, c.end_date, c.destination_url,
       s.slot_key,
       COALESCE(imp.total_impressions, 0) AS impressions,
       COALESCE(clk.total_clicks, 0) AS clicks
FROM wp_poradnik_ad_campaigns c
LEFT JOIN wp_poradnik_ad_slots s ON s.id = c.slot_id
LEFT JOIN (
  SELECT campaign_id, COUNT(*) AS total_impressions
  FROM wp_poradnik_ad_impressions
  GROUP BY campaign_id
) imp ON imp.campaign_id = c.id
LEFT JOIN (
  SELECT campaign_id, COUNT(*) AS total_clicks
  FROM wp_poradnik_ad_clicks
  GROUP BY campaign_id
) clk ON clk.campaign_id = c.id
ORDER BY c.id DESC;
```

Dlaczego wysoki:
- Dwa `DERIVED` podzapytania (`GROUP BY campaign_id`) skanują całe tabele eventów.
- Przy rosnącym wolumenie eventów koszt będzie rosnąć liniowo.

### Q2 — statystyki impressions per advertiser (PRIORYTET: ŚREDNI)
Źródło: `StatsService::statistics()`

```sql
SELECT COUNT(*)
FROM wp_poradnik_ad_impressions i
INNER JOIN wp_poradnik_ad_campaigns c ON c.id = i.campaign_id
WHERE c.advertiser_id = ?;
```

EXPLAIN (stan obecny): indeks `campaign_id` używany, join `eq_ref` po PK kampanii.

Dlaczego średni:
- Zapytanie poprawnie używa indeksów, ale przy dużych wolumenach `COUNT(*)` nadal będzie kosztowny.

### Q3 — statystyki clicks per advertiser (PRIORYTET: ŚREDNI)
Źródło: `StatsService::statistics()`

```sql
SELECT COUNT(*)
FROM wp_poradnik_ad_clicks i
INNER JOIN wp_poradnik_ad_campaigns c ON c.id = i.campaign_id
WHERE c.advertiser_id = ?;
```

EXPLAIN (stan obecny): analogicznie do Q2, indeks `campaign_id` wykorzystywany.

Dlaczego średni:
- Jak Q2: OK dziś, potencjalne obciążenie wraz z wolumenem eventów.

---

## 5) Quick wins na następny sprint

1. **Materialized daily counters** (WYSOKI impact):
   - agregacja `ad_clicks`/`ad_impressions` do tabeli dziennej (`campaign_id`, `day`, `clicks`, `impressions`),
   - dashboard czyta z agregatu zamiast skanować eventy raw.

2. **Cache wyników dashboard statistics/campaigns** (ŚREDNI impact):
   - TTL 30–60s na endpointach dashboardu,
   - natychmiastowy invalidation po zmianie kampanii.

3. **Index review pod zakresy czasu** (ŚREDNI impact):
   - dodać/zweryfikować użycie filtrów `created_at` w zapytaniach raportowych,
   - przygotować indeksy złożone pod najczęstsze WHERE (`campaign_id, created_at`).

4. **Monitoring query latency** (ŚREDNI impact):
   - dzienny raport top zapytań dashboardowych,
   - alarm gdy p95 query time > 300 ms.

---

## 6) Kryterium akceptacji sekcji C

- [x] Baseline TTFB wykonany i zapisany
- [x] Lista top zapytań DB z priorytetami przygotowana
- [x] Quick wins na kolejny sprint zdefiniowane

---

## 7) Linki

- [Playbook komend testowych](PORADNIK-PRO-P1-TEST-COMMANDS-2026-03-13.md)
- [Sprint 12 Plan](PORADNIK-PRO-SPRINT-12-PLAN-2026-03-13.md)
- [Measurement Baseline](PORADNIK-PRO-MEASUREMENT-BASELINE-2026-03.md)
- [Risk Matrix](PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md)
