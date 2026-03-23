# PORADNIK.PRO — P1 Test Commands (PowerShell)

Data: 2026-03-13  
Zakres: P1-04, P1-05, P1-06

## Gate operacyjny (pre-deploy / deploy / rollback)

```powershell
PowerShell -ExecutionPolicy Bypass -File .\tools\rest-smoke.ps1 -BaseUrl https://poradnik.pro -Strict
```

Warunek PASS: `SMOKE_FAILED=0`.

## Production gate (single command)

```powershell
PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro
```

Wymagane markery:
- `PRODUCTION_GATE=PASS`
- `GATE_REST_FAILED=0`
- `GATE_AI_FAILED=0`

Tryb pełnej zgodności AI route policy (na środowisku docelowym `poradnik/v1`):

```powershell
PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro -RequireAiRoutes
```

Uwaga: skrypt automatycznie wykrywa aktywny namespace (`poradnik/v1` lub `peartree/v1`) i dobiera profil endpointów.

## P1-08 — Smoke: AI Assistant + AI Image + Content Engine 3.0 builder

```powershell
PowerShell -ExecutionPolicy Bypass -File .\tools\p1-ai-content-engine-smoke.ps1 -BaseUrl https://poradnik.pro
```

Oczekiwane markery:
- `AI_ASSISTANT_PASS=...`
- `AI_IMAGE_PASS=...`
- `CONTENT_ENGINE_PORADNIK_PASS=...`
- `CONTENT_ENGINE_QA_PASS=...`
- `CONTENT_ENGINE_AFFILIATE_PASS=...`
- `AI_CONTENT_ENGINE_SMOKE=PASS`

W trybie gate:

```powershell
PowerShell -ExecutionPolicy Bypass -File .\tools\p1-ai-content-engine-smoke.ps1 -BaseUrl https://poradnik.pro -Strict
```

Warunek PASS: `AI_SMOKE_FAILED=0`.

## Ustawienia wspólne

```powershell
$BaseUrl = "https://poradnik.pro"   # produkcja
# $BaseUrl = "http://poradnikpro.local"  # lokalnie

# Namespace NIE powinien być hardcodowany.
# Do gate'ów używaj: .\tools\rest-smoke.ps1 (auto-detect namespace).
```

Dla endpointów wymagających logowania użyj sesji/cookies WordPress lub tokena zgodnie z konfiguracją środowiska.

---

## P1-04 — Endpointy prywatne: brak autoryzacji => 401/403

```powershell
# Przykład endpointu prywatnego (podmień namespace na aktywny: poradnik/v1 lub peartree/v1)
$ApiBase = "$BaseUrl/wp-json/<namespace>"
$PrivateEndpoint = "$ApiBase/dashboard/statistics"

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
$ApiBase = "$BaseUrl/wp-json/<namespace>"
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

Jeżeli aktywny jest namespace `peartree/v1`, użyj zamiast tego endpointu publicznego z aktualnego profilu modułu (np. listing publiczny) i zachowaj to samo kryterium walidacji statusu.

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
