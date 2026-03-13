# PORADNIK.PRO — Measurement Baseline (Marzec 2026)

Data: 2026-03-13  
Owner: Platform Team / Analityka  
Status: **PRODUKCYJNA**

---

## 1. Eventy monetyzacyjne — definicje i źródła

| Event | Endpoint REST | Tabela DB | Pola kluczowe | Obsługuje |
|-------|---------------|-----------|---------------|-----------|
| `affiliate_click` | `POST /wp-json/poradnik/v1/affiliate/click` | `wp_poradnik_affiliate_clicks` | `product_id`, `post_id`, `source`, `referrer`, `user_ip` | `AffiliateClickController → ClickTracker` |
| `ad_click` | `POST /wp-json/poradnik/v1/ads/click` | `wp_poradnik_ad_clicks` | `campaign_id`, `slot_id`, `source`, `user_ip` | `AdClickController → Tracker::trackClick` |
| `ad_impression` | `POST /wp-json/poradnik/v1/ads/impression` | `wp_poradnik_ad_impressions` | `campaign_id`, `slot_id`, `source` | `AdImpressionController → Tracker::trackImpression` |

---

## 2. Wyniki walidacji (2026-03-13)

Skrypt: `themes/poradnik-theme/scripts/p1-measurement-events-e2e.ps1`

| Event | API status | DB verify | Cleanup | Wynik |
|-------|-----------|-----------|---------|-------|
| `affiliate_click` | HTTP 200, `click_id=3` | `MEAS_AFF_DB_PASS` | OK | **PASS** |
| `ad_click` | HTTP 200, `click_id=7` | `MEAS_ADCLICK_DB_PASS` | OK | **PASS** |
| `ad_impression` | HTTP 200, `impression_id=1` | `MEAS_ADIMPR_DB_PASS` | OK | **PASS** |

**`MEAS_SCRIPT_EXIT=0`** — wszystkie 3 eventy poprawnie rejestrowane i persystowane.

### Naprawiony bug (przy walidacji)
- **Plik:** `platform-core/Domain/Ads/Tracker.php`
- **Bug:** metoda `track()` zawsze wstawiała kolumnę `user_ip`, ale tabela `wp_poradnik_ad_impressions` nie posiada tej kolumny (tylko `ad_clicks` ma `user_ip`).
- **Fix:** dodano parametr `bool $trackIp = true` do prywatnej metody `track()`; `trackImpression()` wywołuje z `$trackIp = false`.
- **Weryfikacja:** `php -l Tracker.php` — brak błędów składni; `MEAS_ADIMPR_API_PASS` po poprawce.

---

## 3. Schemat tabel (confirmed)

### `wp_poradnik_affiliate_clicks`
```sql
id          bigint(20) unsigned NOT NULL AUTO_INCREMENT
product_id  bigint(20) unsigned NOT NULL
post_id     bigint(20) unsigned DEFAULT NULL
source      varchar(191) DEFAULT ''
referrer    varchar(512) DEFAULT ''
user_ip     varchar(45) DEFAULT ''
created_at  datetime NOT NULL
updated_at  datetime NOT NULL
```

### `wp_poradnik_ad_clicks`
```sql
id           bigint(20) unsigned NOT NULL AUTO_INCREMENT
campaign_id  bigint(20) unsigned NOT NULL
slot_id      bigint(20) unsigned DEFAULT NULL
source       varchar(191) DEFAULT ''
user_ip      varchar(45) DEFAULT ''
created_at   datetime NOT NULL
updated_at   datetime NOT NULL
```

### `wp_poradnik_ad_impressions`
```sql
id           bigint(20) unsigned NOT NULL AUTO_INCREMENT
campaign_id  bigint(20) unsigned NOT NULL
slot_id      bigint(20) unsigned DEFAULT NULL
source       varchar(191) DEFAULT ''
created_at   datetime NOT NULL
updated_at   datetime NOT NULL
```
*(brak `user_ip` — by design po poprawce)*

---

## 4. Baseline wolumenów (pre-go-live)

> Uzupełnić po pierwszych 24h produkcyjnych (D+1).

| Metryka | Wartość baseline | Okno pomiaru | Data pomiaru | Źródło |
|---------|-----------------|--------------|--------------|--------|
| `affiliate_click` / godz. | TBD | 24h po launch | D+1 | `wp_poradnik_affiliate_clicks` |
| `ad_click` / godz. | TBD | 24h po launch | D+1 | `wp_poradnik_ad_clicks` |
| `ad_impression` / godz. | TBD | 24h po launch | D+1 | `wp_poradnik_ad_impressions` |
| Stosunek `ad_click` / `ad_impression` (CTR) | TBD | 24h | D+1 | Joins obu tabel |

**SQL do uzupełnienia baseline (uruchomić D+1):**
```sql
-- Affiliate clicks per hour (ostatnie 24h)
SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS hour,
       COUNT(*) AS clicks
FROM wp_poradnik_affiliate_clicks
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY hour ORDER BY hour;

-- Ad clicks per hour (ostatnie 24h)
SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS hour,
       COUNT(*) AS clicks
FROM wp_poradnik_ad_clicks
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY hour ORDER BY hour;

-- Ad impressions per hour (ostatnie 24h)
SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS hour,
       COUNT(*) AS impressions
FROM wp_poradnik_ad_impressions
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY hour ORDER BY hour;

-- CTR ogólny (ostatnie 24h)
SELECT
  (SELECT COUNT(*) FROM wp_poradnik_ad_clicks WHERE created_at >= NOW() - INTERVAL 24 HOUR) AS total_clicks,
  (SELECT COUNT(*) FROM wp_poradnik_ad_impressions WHERE created_at >= NOW() - INTERVAL 24 HOUR) AS total_impressions,
  ROUND(
    (SELECT COUNT(*) FROM wp_poradnik_ad_clicks WHERE created_at >= NOW() - INTERVAL 24 HOUR) /
    NULLIF((SELECT COUNT(*) FROM wp_poradnik_ad_impressions WHERE created_at >= NOW() - INTERVAL 24 HOUR), 0) * 100,
    2
  ) AS ctr_pct;
```

---

## 5. Raport kontrolny dzienny (szablon)

> Uruchamiać codziennie o 08:00 przez pierwszych 7 dni po launch.

Automatyzacja:
- skrypt: `themes/poradnik-theme/scripts/p1-measurement-daily-report.ps1`
- wygenerowany artefakt (dzisiaj): `PORADNIK-PRO-MEASUREMENT-DAILY-REPORT-2026-03-13.md`

| Pole | Wartość |
|------|---------|
| Data raportu | YYYY-MM-DD |
| `affiliate_click` dzienny | N |
| `ad_click` dzienny | N |
| `ad_impression` dzienny | N |
| CTR (`ad_click` / `ad_impression`) | N% |
| Błędy zapisu (5xx tracking endpoints) | N |
| Anomalie (odchylenie > 50% od baseline) | TAK / NIE |
| Akcja | — |

---

## 6. Progi alertów (z matrycy ryzyk)

| Event | Próg WARN | Próg CRIT |
|-------|-----------|-----------|
| `affiliate_click` / godz. | < 50% baseline | < 20% baseline |
| `ad_click` / godz. | < 50% baseline | < 20% baseline |
| `ad_impression` / godz. | < 50% baseline | < 20% baseline |

*Pełna matryca: `PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md`*

---

## 7. Linki do artefaktów

- [Skrypt walidacji](../themes/poradnik-theme/scripts/p1-measurement-events-e2e.ps1)
- [Skrypt raportu dziennego](../themes/poradnik-theme/scripts/p1-measurement-daily-report.ps1)
- [Matryca ryzyk](PORADNIK-PRO-GOLIVE-RISK-MATRIX-2026-03.md)
- [Go-Live Checklist](PORADNIK-PRO-GO-LIVE-CHECKLIST-2026-03.md)
- [Sprint 12 Plan](PORADNIK-PRO-SPRINT-12-PLAN-2026-03-13.md)
- [Performance Baseline](PORADNIK-PRO-PERFORMANCE-BASELINE-2026-03.md)
