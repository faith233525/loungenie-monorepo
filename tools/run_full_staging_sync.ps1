<#
Run full staging sync: installer, template import, FTP backup, media sync.
- Prompts for WP and FTP credentials (secure input)
- Defaults to dry-run; pass -Live to perform live changes

Usage:
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_full_staging_sync.ps1      # dry-run
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_full_staging_sync.ps1 -Live  # live

IMPORTANT: Do NOT paste secrets in chat. Run this locally or in a secure CI environment.
#>
param(
    [switch]$Live
)

function Read-SecureStringToPlain([string]$prompt){
    $s = Read-Host -Prompt $prompt -AsSecureString
    $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s)
    try { $plain = [Runtime.InteropServices.Marshal]::PtrToStringAuto($b) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }
    return $plain
}

Write-Host "This script will run the staging sync tasks. It prompts for credentials and won't store them on disk." -ForegroundColor Cyan

$WP_URL = Read-Host "WP site URL (e.g. https://loungenie.com/staging)"
$WP_USER = Read-Host "WP username for App Password (e.g. copilot)"
$WP_APP_PASS = Read-SecureStringToPlain "WP Application Password (will be read securely)"

$FTP_HOST = Read-Host "FTP host (e.g. ftp.poolsafeinc.com)"
$FTP_USER = Read-Host "FTP user (e.g. copilot@loungenie.com)"
$FTP_PASS = Read-SecureStringToPlain "FTP password (will be read securely)"

# Set env vars for child processes
$env:WP_URL = $WP_URL
$env:WP_USER = $WP_USER
$env:WP_APP_PASS = $WP_APP_PASS
$env:FTP_HOST = $FTP_HOST
$env:FTP_USER = $FTP_USER
$env:FTP_PASS = $FTP_PASS

if ($Live) { Write-Host "Running in LIVE mode — changes will be applied." -ForegroundColor Yellow } else { Write-Host "Dry-run mode (no destructive actions). Pass -Live to apply changes." -ForegroundColor Green; $env:DRY_RUN='true' }

# 1) Install Kadence + recommended plugins (wp-cli script)
Write-Host "\n=== Step 1: Kadence + Plugins (wp-cli) ===" -ForegroundColor Cyan
if (Get-Command wp -ErrorAction SilentlyContinue) {
    if ($Live) { & pwsh -NoProfile -Command "& .\tools\install_kadence_wpcli.ps1 -WPPath ." } else { & pwsh -NoProfile -Command "& .\tools\install_kadence_wpcli.ps1 -WPPath . -DryRun" }
} else { Write-Host "wp-cli not found. Please install wp-cli or run the manual steps in docs/KADENCE_INSTALL.md" -ForegroundColor Yellow }

# 2) Import header/footer template-parts via REST (preserve Gutenberg blocks)
Write-Host "\n=== Step 2: Import header/footer template-parts ===" -ForegroundColor Cyan
Write-Host "Importing header..." -NoNewline; & python .\scripts\wp_import_template_part.py .\artifacts\lg9_header_template_part.json --apply; Write-Host " (header attempted)"
Write-Host "Importing footer..." -NoNewline; & python .\scripts\wp_import_template_part.py .\artifacts\lg9_footer_template_part.json --apply; Write-Host " (footer attempted)"

# 3) FTP backup (safe orchestrator)
Write-Host "\n=== Step 3: FTP backup (safe) ===" -ForegroundColor Cyan
if (-not $Live) { $env:DRY_RUN='true' } else { $env:DRY_RUN='false' }
$env:RUN_FTP_BACKUP='true'
& pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\run_wp_ftp_and_rest_safe.ps1

# 4) Media sync
Write-Host "\n=== Step 4: Media sync ===" -ForegroundColor Cyan
if ($Live) {
    Write-Host "Running media sync (LIVE)"
    & python .\scripts\sync_media.py --source artifacts/uploads
} else {
    Write-Host "Running media sync (dry-run)"
    & python .\scripts\sync_media.py --source artifacts/uploads --dry-run
}

Write-Host "\nAll steps completed. Review output above and artifacts/media_uploads.json for media results." -ForegroundColor Green
# Clear sensitive env vars from this process
Remove-Variable WP_APP_PASS -ErrorAction SilentlyContinue; Remove-Variable FTP_PASS -ErrorAction SilentlyContinue
$env:WP_APP_PASS=''; $env:FTP_PASS=''

Write-Host "Credentials cleared from environment variables in this session." -ForegroundColor Gray
