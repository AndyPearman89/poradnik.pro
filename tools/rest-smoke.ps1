param(
    [string]$BaseUrl = 'https://poradnik.pro',
    [switch]$Strict
)

$ErrorActionPreference = 'Stop'

function Invoke-Endpoint {
    param(
        [Parameter(Mandatory = $true)][string]$Url,
        [Parameter(Mandatory = $true)][int[]]$AllowedStatusCodes
    )

    try {
        $response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 20 -ErrorAction Stop
        $code = [int]$response.StatusCode
    }
    catch {
        if ($_.Exception.Response) {
            $code = [int]$_.Exception.Response.StatusCode
        }
        else {
            $code = -1
        }
    }

    $ok = $AllowedStatusCodes -contains $code
    [pscustomobject]@{
        url = $Url
        status = $code
        ok = $ok
        allowed = ($AllowedStatusCodes -join ',')
    }
}

$base = $BaseUrl.TrimEnd('/')
$checks = @(
    @{ path = '/wp-json/'; allowed = @(200) },
    @{ path = '/wp-json/poradnik/v1/'; allowed = @(200) },
    @{ path = '/wp-json/poradnik/v1/health'; allowed = @(200) },
    @{ path = '/wp-json/poradnik/v1/dashboard/statistics'; allowed = @(200, 401, 403) },
    @{ path = '/wp-json/poradnik/v1/affiliate/products'; allowed = @(200, 401, 403) }
)

$results = foreach ($check in $checks) {
    Invoke-Endpoint -Url ($base + $check.path) -AllowedStatusCodes $check.allowed
}

$results | Format-Table -AutoSize

$failed = @($results | Where-Object { -not $_.ok }).Count
Write-Host "`nSMOKE_BASE=$base"
Write-Host "SMOKE_TOTAL=$($results.Count)"
Write-Host "SMOKE_FAILED=$failed"

if ($Strict -and $failed -gt 0) {
    Write-Error "REST smoke failed in strict mode ($failed failed checks)."
}

exit 0
