param(
    [string]$BaseUrl = 'https://poradnik.pro',
    [switch]$Strict
)

$ErrorActionPreference = 'Stop'

function Resolve-Namespace {
    param(
        [Parameter(Mandatory = $true)][string]$BaseUrl
    )

    try {
        $meta = Invoke-RestMethod -Uri ($BaseUrl.TrimEnd('/') + '/wp-json/') -Method Get -TimeoutSec 20 -ErrorAction Stop
        $namespaces = @($meta.namespaces)

        if ($namespaces -contains 'poradnik/v1') {
            return 'poradnik/v1'
        }

        if ($namespaces -contains 'peartree/v1') {
            return 'peartree/v1'
        }
    }
    catch {
        return ''
    }

    return ''
}

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
$namespace = Resolve-Namespace -BaseUrl $base

if ($namespace -eq '') {
    $namespace = 'poradnik/v1'
}

$checks = @(@{ path = '/wp-json/'; allowed = @(200) })

if ($namespace -eq 'poradnik/v1') {
    $checks += @{ path = "/wp-json/$namespace/"; allowed = @(200) }
    $checks += @{ path = "/wp-json/$namespace/health"; allowed = @(200) }
    $checks += @{ path = "/wp-json/$namespace/dashboard/statistics"; allowed = @(200, 401, 403) }
    $checks += @{ path = "/wp-json/$namespace/affiliate/products"; allowed = @(200, 401, 403) }
}
elseif ($namespace -eq 'peartree/v1') {
    $checks += @{ path = "/wp-json/$namespace/system/health"; allowed = @(200, 503) }
    $checks += @{ path = "/wp-json/$namespace/listings"; allowed = @(200) }
    $checks += @{ path = "/wp-json/$namespace/dashboard/admin?days=30"; allowed = @(200, 401, 403) }
}
else {
    $checks += @{ path = "/wp-json/$namespace/"; allowed = @(200) }
}

$results = foreach ($check in $checks) {
    Invoke-Endpoint -Url ($base + $check.path) -AllowedStatusCodes $check.allowed
}

$results | Format-Table -AutoSize

$failed = @($results | Where-Object { -not $_.ok }).Count
Write-Host "`nSMOKE_BASE=$base"
Write-Host "SMOKE_NAMESPACE=$namespace"
Write-Host "SMOKE_TOTAL=$($results.Count)"
Write-Host "SMOKE_FAILED=$failed"

if ($Strict -and $failed -gt 0) {
    Write-Error "REST smoke failed in strict mode ($failed failed checks)."
}

exit 0
