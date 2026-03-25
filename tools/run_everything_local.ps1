<#
Run all local prep tasks for staging restoration.

Usage:
  # Dry-run (default): runs audits, duplicate detection, ALT suggestions, and invokes orchestrator in dry-run
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_everything_local.ps1

  # Live run (will prompt for credentials in the orchestrator):
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_everything_local.ps1 -Live

Notes:
  - This script does NOT accept secrets on the command line. The orchestrator will prompt securely if you choose Live.
  - All artifacts are written under the `artifacts/` folder.
#>
param(
    [switch]$Live
)

Set-StrictMode -Version Latest
$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
$repoRoot = Resolve-Path (Join-Path $scriptRoot "..")
$artifacts = Join-Path $repoRoot 'artifacts'
if (-not (Test-Path $artifacts)) { New-Item -ItemType Directory -Force -Path $artifacts | Out-Null }

function Run-Cmd($cmd, $desc) {
    Write-Host "\n==> $desc"
    Write-Host "Command: $cmd" -ForegroundColor DarkGray
    $rc = & cmd /c $cmd
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Command failed with exit code $LASTEXITCODE"
    }
}

try {
    Write-Host "Starting full local run: $(Get-Date -Format o)" -ForegroundColor Cyan

    # Activate virtualenv if present (Windows)
    $venvActivate = Join-Path $repoRoot "venv\Scripts\Activate.ps1"
    if (Test-Path $venvActivate) {
        Write-Host "Activating venv..."
        & $venvActivate
    } else {
        Write-Host "No venv activation found at $venvActivate — ensure python is available on PATH." -ForegroundColor Yellow
    }

    # 1) Duplicate detection
    Write-Host "Running duplicate-artifact detector..."
    & python "$repoRoot\scripts\find_artifact_duplicates.py" > "$artifacts\duplicate_report.txt"

    # 2) Automated audits (images, colors, broken refs)
    Write-Host "Running automated audits (image, color, broken refs)..."
    pwsh -NoProfile -ExecutionPolicy Bypass -File "$repoRoot\scripts\run_automated_audits.ps1"

    # 3) ALT suggestions and oversized images
    Write-Host "Generating ALT suggestions and oversized image list..."
    & python "$repoRoot\scripts\generate_alt_and_oversized.py"

    # 4) Optional: run orchestrator (dry-run by default)
    if ($Live) {
        Write-Host "LIVE MODE: invoking orchestrator (will prompt for credentials)." -ForegroundColor Red
        pwsh -NoProfile -ExecutionPolicy Bypass -File "$repoRoot\tools\run_full_staging_sync.ps1" -Live
    } else {
        Write-Host "Dry-run: invoking orchestrator in dry-run mode (no remote writes)." -ForegroundColor Green
        pwsh -NoProfile -ExecutionPolicy Bypass -File "$repoRoot\tools\run_full_staging_sync.ps1"
    }

    Write-Host "\nAll steps finished. Artifacts written to: $artifacts" -ForegroundColor Cyan
    Write-Host "Key files: duplicate_report.txt, audit_all_images.txt, audit_colors.txt, check_broken_references.txt, alt_suggestions.csv, oversized_images.csv, canonical_navigation_mapping.json" -ForegroundColor Gray
    exit 0
}
catch {
    Write-Error $_.Exception.Message
    exit 2
}
