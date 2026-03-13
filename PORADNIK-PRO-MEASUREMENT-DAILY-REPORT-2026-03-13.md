# PORADNIK.PRO â€” Raport kontrolny dzienny (Measurement)

Data raportu: 2026-03-13  
Timestamp generacji: 2026-03-13 19:15:39  
Generator: p1-measurement-daily-report.ps1

## Podsumowanie 24h

| Pole | WartoĹ›Ä‡ |
|---|---:|
| affiliate_click (24h) | 1 |
| ad_click (24h) | 7 |
| ad_impression (24h) | 3 |
| CTR (ad_click / ad_impression) | 233.33% |
| BĹ‚Ä™dy zapisu (log patterns, 24h) | 0 |
| Anomalie | NIE |
| Akcja | Brak akcji â€” monitoring standardowy. |

## SQL ĹşrĂłdĹ‚owe

`sql
SELECT COUNT(*) FROM wp_poradnik_affiliate_clicks WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR;
SELECT COUNT(*) FROM wp_poradnik_ad_clicks WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR;
SELECT COUNT(*) FROM wp_poradnik_ad_impressions WHERE created_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR;
`

## Uwagi
- Liczniki bĹ‚Ä™dĂłw opierajÄ… siÄ™ o wzorce w wp-content/debug.log (jeĹ›li plik istnieje).
- Raport przeznaczony do codziennego uruchamiania w pierwszych 7 dniach po Go-Live.
