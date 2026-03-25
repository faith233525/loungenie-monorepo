# Force redeploy all page artifacts (overwrite existing results)
$PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
. "$PSScriptRoot\secrets.ps1"
$env:WP_URL = 'https://loungenie.com/staging'
$outDir = Join-Path $PSScriptRoot '..\outputs\post_results'
if(-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
$files = Get-ChildItem (Join-Path $PSScriptRoot '..\artifacts\page_*_update.json') -ErrorAction SilentlyContinue
if(-not $files) { Write-Host 'No page artifacts found in artifacts/'; exit 0 }
foreach($f in $files) {
    $out = Join-Path $outDir "$($f.BaseName)_result.json"
    $err = Join-Path $outDir "$($f.BaseName)_error.txt"
    Write-Host "--- Deploying $($f.Name) ---"
    & (Join-Path $PSScriptRoot '..\venv\Scripts\python.exe') (Join-Path $PSScriptRoot '..\scripts\deploy_page.py') $f.FullName > $out 2> $err
    Write-Host "Exit code: $LASTEXITCODE"
    Start-Sleep -Milliseconds 300
}
Write-Host 'Done'
