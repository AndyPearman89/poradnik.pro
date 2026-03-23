param(
    [string]$BaseUrl = 'https://poradnik.pro',
    [switch]$RequireAiRoutes
)

$ErrorActionPreference = 'Stop'

function Invoke-ScriptCapture {
    param(
        [Parameter(Mandatory = $true)][string]$ScriptPath,
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )

    $cmd = @($ScriptPath) + $Arguments
    $output = & PowerShell -ExecutionPolicy Bypass -File @cmd 2>&1
    $exitCode = $LASTEXITCODE

    return [pscustomobject]@{
        output = @($output)
        exitCode = $exitCode
    }
}

function Get-MarkerValue {
    param(
        [Parameter(Mandatory = $true)][object[]]$Lines,
        [Parameter(Mandatory = $true)][string]$Marker
    )

    foreach ($line in @($Lines)) {
        $text = [string]$line
        if ($text -eq '') {
            continue
        }

        if ($text -match ('^' + [regex]::Escape($Marker) + '=(.+)$')) {
            return $Matches[1].Trim()
        }
    }

    return ''
}

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host '=== PORADNIK.PRO PRODUCTION GATE ==='
Write-Host ('BASE_URL=' + $BaseUrl)
Write-Host ('GATE_TIMESTAMP=' + (Get-Date -Format 'yyyy-MM-ddTHH:mm:ssK'))

$rest = Invoke-ScriptCapture -ScriptPath '.\tools\rest-smoke.ps1' -Arguments @('-BaseUrl', $BaseUrl, '-Strict')
$rest.output | ForEach-Object { Write-Host $_ }

$ai = Invoke-ScriptCapture -ScriptPath '.\tools\p1-ai-content-engine-smoke.ps1' -Arguments @('-BaseUrl', $BaseUrl, '-Strict')
$ai.output | ForEach-Object { Write-Host $_ }

$restFailed = Get-MarkerValue -Lines $rest.output -Marker 'SMOKE_FAILED'
$aiFailed = Get-MarkerValue -Lines $ai.output -Marker 'AI_SMOKE_FAILED'

if ($restFailed -eq '') {
    $restFailed = '-1'
}

if ($aiFailed -eq '') {
    $aiFailed = '-1'
}

$aiSkipped = @($ai.output | Where-Object { $_ -match 'SKIPPED_ROUTE_NOT_FOUND' }).Count

Write-Host ''
Write-Host ('GATE_REST_EXIT=' + $rest.exitCode)
Write-Host ('GATE_AI_EXIT=' + $ai.exitCode)
Write-Host ('GATE_REST_FAILED=' + $restFailed)
Write-Host ('GATE_AI_FAILED=' + $aiFailed)
Write-Host ('GATE_AI_SKIPPED_ROUTES=' + $aiSkipped)

$failed = $false

if ($rest.exitCode -ne 0) {
    $failed = $true
}

if ($ai.exitCode -ne 0) {
    $failed = $true
}

if ([int]$restFailed -gt 0) {
    $failed = $true
}

if ([int]$aiFailed -gt 0) {
    $failed = $true
}

if ($RequireAiRoutes -and $aiSkipped -gt 0) {
    Write-Host 'GATE_AI_ROUTE_POLICY=FAIL (RequireAiRoutes enabled and one or more AI routes were skipped)'
    $failed = $true
}
else {
    Write-Host 'GATE_AI_ROUTE_POLICY=PASS'
}

if ($failed) {
    Write-Host 'PRODUCTION_GATE=FAIL'
    exit 1
}

Write-Host 'PRODUCTION_GATE=PASS'
exit 0
