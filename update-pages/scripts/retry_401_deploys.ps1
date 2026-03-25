# Retry deployments that previously returned 401
$PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
. "$PSScriptRoot\secrets.ps1"
$env:WP_URL = 'https://loungenie.com/staging'
$resultsPath = Join-Path $PSScriptRoot '..\outputs\post_results'
$files = Get-ChildItem $resultsPath -Filter 'page_*_result.json' -ErrorAction SilentlyContinue
$toRetry = @()
foreach($f in $files) {
    if(Select-String -Path $f.FullName -Pattern 'Request failed: 401' -Quiet) { $toRetry += $f }
}
if(-not $toRetry) { Write-Host 'No 401 results to retry.'; exit 0 }
foreach($r in $toRetry) {
    $base = $r.BaseName -replace '_result$',''
    $artifact = Join-Path $PSScriptRoot "..\artifacts\$base.json"
    $out = $r.FullName
    $err = Join-Path $resultsPath "$base`_error.txt"
    Write-Host "Retrying $artifact"
    & (Join-Path $PSScriptRoot '..\venv\Scripts\python.exe') (Join-Path $PSScriptRoot '..\scripts\deploy_page.py') $artifact > $out 2> $err
    Write-Host "Exit code: $LASTEXITCODE"
    Start-Sleep -Milliseconds 300
}
Write-Host 'Retries complete.'
