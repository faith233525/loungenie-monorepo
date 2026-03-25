# Deploy all page artifacts that don't yet have results
. "$PSScriptRoot\secrets.ps1"
$env:WP_URL = 'https://loungenie.com/staging'
$outDir = Join-Path $PSScriptRoot '..\outputs\post_results' | Resolve-Path -ErrorAction SilentlyContinue
if(-not $outDir) { New-Item -ItemType Directory -Path (Join-Path $PSScriptRoot '..\outputs\post_results') | Out-Null }
$files = Get-ChildItem (Join-Path $PSScriptRoot '..\artifacts\page_*_update.json') -ErrorAction SilentlyContinue
if(-not $files) { Write-Host 'No page artifacts found in artifacts/'; exit 0 }
foreach($f in $files) {
    $out = Join-Path $PSScriptRoot "..\outputs\post_results\$($f.BaseName)_result.json"
    $err = Join-Path $PSScriptRoot "..\outputs\post_results\$($f.BaseName)_error.txt"
    if(Test-Path $out) { Write-Host "Skipping (exists): $($f.Name)"; continue }
    Write-Host "--- Deploying $($f.Name) ---"
    & (Join-Path $PSScriptRoot '..\venv\Scripts\python.exe') (Join-Path $PSScriptRoot '..\scripts\deploy_page.py') $f.FullName > $out 2> $err
    Write-Host "Exit code: $LASTEXITCODE"
    Start-Sleep -Milliseconds 300
}
Write-Host 'Done'
