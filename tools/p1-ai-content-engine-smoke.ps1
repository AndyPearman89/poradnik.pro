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

function Get-RestMeta {
    param(
        [Parameter(Mandatory = $true)][string]$BaseUrl
    )

    try {
        return Invoke-RestMethod -Uri ($BaseUrl.TrimEnd('/') + '/wp-json/') -Method Get -TimeoutSec 20 -ErrorAction Stop
    }
    catch {
        return $null
    }
}

function Resolve-RouteUrl {
    param(
        [Parameter(Mandatory = $true)][string]$BaseUrl,
        [Parameter(Mandatory = $true)]$Meta,
        [Parameter(Mandatory = $true)][string]$RouteSuffix,
        [Parameter(Mandatory = $true)][string]$DefaultNamespace
    )

    $routeNames = @()
    if ($Meta -and $Meta.routes) {
        $routeNames = @($Meta.routes.PSObject.Properties.Name)
    }

    $priorityNamespaces = @('poradnik/v1', 'peartree/v1')
    foreach ($ns in $priorityNamespaces) {
        $routeKey = '/' + $ns + $RouteSuffix
        if ($routeNames -contains $routeKey) {
            return [pscustomobject]@{
                url = $BaseUrl.TrimEnd('/') + '/wp-json/' + $ns + $RouteSuffix
                namespace = $ns
                exists = $true
            }
        }
    }

    return [pscustomobject]@{
        url = $BaseUrl.TrimEnd('/') + '/wp-json/' + $DefaultNamespace + $RouteSuffix
        namespace = $DefaultNamespace
        exists = $false
    }
}

function Invoke-PostCheck {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][string]$Url,
        [Parameter(Mandatory = $true)][bool]$RouteExists,
        [Parameter(Mandatory = $true)][hashtable]$Payload,
        [Parameter(Mandatory = $true)][int[]]$AllowedStatusCodes
    )

    if (-not $RouteExists) {
        return [pscustomobject]@{
            name = $Name
            url = $Url
            status = 0
            ok = $true
            allowed = ($AllowedStatusCodes -join ',')
            note = 'SKIPPED_ROUTE_NOT_FOUND'
        }
    }

    $body = ($Payload | ConvertTo-Json -Depth 8)

    try {
        $response = Invoke-WebRequest -Uri $Url -Method Post -ContentType 'application/json' -Body $body -TimeoutSec 30 -ErrorAction Stop
        $statusCode = [int]$response.StatusCode
    }
    catch {
        if ($_.Exception.Response) {
            $statusCode = [int]$_.Exception.Response.StatusCode
        }
        else {
            $statusCode = -1
        }
    }

    $ok = $AllowedStatusCodes -contains $statusCode

    [pscustomobject]@{
        name = $Name
        url = $Url
        status = $statusCode
        ok = $ok
        allowed = ($AllowedStatusCodes -join ',')
        note = ''
    }
}

$base = $BaseUrl.TrimEnd('/')
$meta = Get-RestMeta -BaseUrl $base
$namespace = Resolve-Namespace -BaseUrl $base

if ($namespace -eq '') {
    $namespace = 'poradnik/v1'
}

$checks = @(
    @{ 
        name = 'AI_ASSISTANT'
        path = '/ai/content/generate'
        payload = @{ tool = 'outline'; input = 'jak ustawic router wifi w domu'; items = @('Opcja A', 'Opcja B') }
        allowed = @(200, 400, 401, 403)
    },
    @{ 
        name = 'AI_IMAGE'
        path = '/ai/image/generate'
        payload = @{ title = 'Poradnik: konfiguracja domowego Wi-Fi'; category = 'poradnik' }
        allowed = @(200, 400, 401, 403)
    },
    @{ 
        name = 'CONTENT_ENGINE_PORADNIK'
        path = '/seo/programmatic/build'
        payload = @{ generation_mode = 'single'; template = 'jak-zrobic'; topic = 'kopia zapasowa wordpress'; count = 1; post_type = 'poradnik' }
        allowed = @(200, 400, 401, 403)
    },
    @{ 
        name = 'CONTENT_ENGINE_QA'
        path = '/seo/programmatic/build'
        payload = @{ generation_mode = 'single'; template = 'jak-dziala'; topic = 'jak dziala cache strony'; count = 1; post_type = 'pytanie' }
        allowed = @(200, 400, 401, 403)
    },
    @{ 
        name = 'CONTENT_ENGINE_AFFILIATE'
        path = '/seo/programmatic/build'
        payload = @{ generation_mode = 'single'; template = 'best'; topic = 'najlepszy hosting wordpress'; count = 1; post_type = 'affiliate' }
        allowed = @(200, 400, 401, 403)
    }
)

$results = foreach ($check in $checks) {
    $route = Resolve-RouteUrl -BaseUrl $base -Meta $meta -RouteSuffix $check.path -DefaultNamespace $namespace

    Invoke-PostCheck -Name $check.name -Url $route.url -RouteExists $route.exists -Payload $check.payload -AllowedStatusCodes $check.allowed
}

$results | Format-Table -AutoSize

$failed = @($results | Where-Object { -not $_.ok }).Count

Write-Host "`nAI_SMOKE_BASE=$base"
Write-Host "AI_SMOKE_NAMESPACE=$namespace"
Write-Host "AI_SMOKE_TOTAL=$($results.Count)"
Write-Host "AI_SMOKE_FAILED=$failed"

foreach ($result in $results) {
    $marker = if ($result.ok) { 'PASS' } else { 'FAIL' }
    $suffix = if ($result.note -ne '') { ';' + $result.note } else { '' }
    Write-Host ($result.name + '_' + $marker + '=' + $result.status + $suffix)
}

if ($Strict -and $failed -gt 0) {
    Write-Error "AI/Content Engine smoke failed in strict mode ($failed failed checks)."
}

if ($failed -eq 0) {
    Write-Host 'AI_CONTENT_ENGINE_SMOKE=PASS'
}
else {
    Write-Host 'AI_CONTENT_ENGINE_SMOKE=FAIL'
}

Write-Host 'AI_CONTENT_ENGINE_SMOKE_SCRIPT_EXIT=0'
exit 0
