# PORADNIK.PRO — P1 Test Commands (PowerShell)

Data: 2026-03-13  
Zakres: P1-04, P1-05, P1-06

## Ustawienia wspólne

```powershell
$BaseUrl = "http://poradnikpro.local"
$ApiBase = "$BaseUrl/wp-json/poradnik/v1"
```

Dla endpointów wymagających logowania użyj sesji/cookies WordPress lub tokena zgodnie z konfiguracją środowiska.

---

## P1-04 — Endpointy prywatne: brak autoryzacji => 401/403

```powershell
# Przykład endpointu prywatnego (podmień na właściwy)
$PrivateEndpoint = "$ApiBase/dashboard"

try {
    Invoke-RestMethod -Uri $PrivateEndpoint -Method GET -ErrorAction Stop
    Write-Host "UNEXPECTED: request succeeded without auth"
} catch {
    $status = $_.Exception.Response.StatusCode.value__
    Write-Host "Status:" $status
    $_.ErrorDetails.Message
}
```

Oczekiwane: status `401` lub `403`.

---

## P1-05 — Endpointy publiczne: niepoprawny payload => 400

```powershell
# Przykład endpointu publicznego (podmień na właściwy)
$PublicEndpoint = "$ApiBase/affiliate/click"

# Payload niepoprawny: błędne typy / brak pól
$BadPayload = @{
    product_id = "abc"
    source     = ""
    post_id    = -1
} | ConvertTo-Json

try {
    Invoke-RestMethod -Uri $PublicEndpoint -Method POST -ContentType "application/json" -Body $BadPayload -ErrorAction Stop
    Write-Host "UNEXPECTED: invalid payload accepted"
} catch {
    $status = $_.Exception.Response.StatusCode.value__
    Write-Host "Status:" $status
    $_.ErrorDetails.Message
}
```

Oczekiwane: status `400` i komunikat walidacji.

---

## P1-06 — E2E (szkielet automatyzacji)

### A) Sponsored workflow (manual + API checks)
```powershell
# 1) Utwórz zamówienie sponsored (endpoint podmień)
$SponsoredCreate = "$ApiBase/sponsored/orders"
$SponsoredPayload = @{ title = "Test sponsored"; email = "qa@example.com" } | ConvertTo-Json
Invoke-RestMethod -Uri $SponsoredCreate -Method POST -ContentType "application/json" -Body $SponsoredPayload
```

Automatyczny smoke admin workflow (`submit -> review -> paid -> publish`):
```powershell
Set-Location "wp-content/themes/poradnik-theme/scripts"
.\p1-sponsored-smoke.ps1
```

### B) Ad campaign workflow
```powershell
# 2) Sprawdź listę/statystyki kampanii (endpoint podmień)
$CampaignStats = "$ApiBase/dashboard/campaigns"
Invoke-RestMethod -Uri $CampaignStats -Method GET
```

### C) Affiliate tracking
```powershell
# 3) Wyślij tracking click
$AffiliateClick = "$ApiBase/affiliate/click"
$ClickPayload = @{ product_id = 123; source = "e2e"; post_id = 456 } | ConvertTo-Json
Invoke-RestMethod -Uri $AffiliateClick -Method POST -ContentType "application/json" -Body $ClickPayload
```

---

## Rejestrowanie dowodu
Po każdym teście dopisz do raportu:
- dokładny endpoint,
- payload/request,
- status code,
- skrót odpowiedzi,
- timestamp.

---

## P2 — Performance baseline (Sprint 12 / C)

```powershell
Set-Location "wp-content/themes/poradnik-theme/scripts"
.\p1-performance-baseline.ps1
```

Oczekiwane:
- linie `PERF_BASELINE|...` dla 4 endpointów,
- `PERF_CLEANUP_PASS`,
- `PERF_SCRIPT_EXIT=0`.

---

## P2 — Measurement daily report (Sprint 12 / B)

```powershell
Set-Location "wp-content/themes/poradnik-theme/scripts"
.\p1-measurement-daily-report.ps1
```

Oczekiwane:
- `MEAS_DAILY_REPORT_FILE=...`
- `MEAS_DAILY_REPORT_PASS`
- `MEAS_DAILY_SCRIPT_EXIT=0`
