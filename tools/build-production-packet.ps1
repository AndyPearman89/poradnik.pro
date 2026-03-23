param(
    [string]$OutputDir = '.\artifacts',
    [string]$PacketName = ''
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$files = @(
    'backend/Api/Controllers/AiContentController.php',
    'backend/Api/Controllers/AiImageController.php',
    'backend/Api/Controllers/ProgrammaticBuildController.php',
    'backend/Core/ContentTypeMapper.php',
    'backend/Domain/Seo/ProgrammaticGenerator.php',
    'tools/p1-ai-content-engine-smoke.ps1',
    'tools/production-gate.ps1'
)

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
if ([string]::IsNullOrWhiteSpace($PacketName)) {
    $PacketName = "poradnik-production-namespace-fix-$timestamp"
}

$artifactRoot = Resolve-Path .
$outputPath = Join-Path $artifactRoot $OutputDir

if (-not (Test-Path $outputPath)) {
    New-Item -ItemType Directory -Path $outputPath | Out-Null
}

$stagingDir = Join-Path $outputPath ($PacketName + '-staging')
if (Test-Path $stagingDir) {
    Remove-Item -Path $stagingDir -Recurse -Force
}
New-Item -ItemType Directory -Path $stagingDir | Out-Null

$missing = @()
foreach ($relative in $files) {
    $source = Join-Path $root $relative

    if (-not (Test-Path $source)) {
        $missing += $relative
        continue
    }

    $target = Join-Path $stagingDir $relative
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }

    Copy-Item -Path $source -Destination $target -Force
}

if ($missing.Count -gt 0) {
    Write-Error ('Missing required files: ' + ($missing -join ', '))
}

$manifestPath = Join-Path $outputPath ($PacketName + '.manifest.txt')
$zipPath = Join-Path $outputPath ($PacketName + '.zip')

$manifest = @()
$manifest += 'PORADNIK.PRO PRODUCTION DEPLOY PACKET'
$manifest += ('GeneratedAt=' + (Get-Date -Format 'yyyy-MM-ddTHH:mm:ssK'))
$manifest += ('PacketName=' + $PacketName)
$manifest += 'Purpose=Namespace fix + production gate routes for AI/SEO'
$manifest += ''
$manifest += 'Files:'
$manifest += $files
$manifest += ''
$manifest += 'Verification command:'
$manifest += 'PowerShell -ExecutionPolicy Bypass -File .\tools\production-gate.ps1 -BaseUrl https://poradnik.pro -RequireAiRoutes'
$manifest += ''
$manifest += 'Expected markers:'
$manifest += 'PRODUCTION_GATE=PASS'
$manifest += 'GATE_AI_SKIPPED_ROUTES=0'

$manifest | Set-Content -Path $manifestPath -Encoding UTF8

if (Test-Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}

Compress-Archive -Path (Join-Path $stagingDir '*') -DestinationPath $zipPath -CompressionLevel Optimal

Remove-Item -Path $stagingDir -Recurse -Force

Write-Host ('DEPLOY_PACKET_ZIP=' + $zipPath)
Write-Host ('DEPLOY_PACKET_MANIFEST=' + $manifestPath)
Write-Host 'DEPLOY_PACKET_STATUS=PASS'
exit 0
