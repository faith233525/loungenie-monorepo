param(
    [string]$WPPath = ".",
    [switch]$DryRun
)

function Write-Info($m) { Write-Host "[info] $m" }
function Write-Warn($m) { Write-Host "[warn] $m" -ForegroundColor Yellow }

Write-Info "Preparing Kadence + Gutenberg install (wp-cli) for path: $WPPath"

$wp = Get-Command wp -ErrorAction SilentlyContinue
if (-not $wp) {
    Write-Warn "wp-cli not found on PATH. Install wp-cli or use the manual steps in docs/KADENCE_INSTALL.md"
    exit 0
}

$commands = @(
    "wp --path='$WPPath' theme install kadence --activate",
    "wp --path='$WPPath' plugin install kadence-blocks --activate",
    "wp --path='$WPPath' plugin install kadence-blocks-pro --activate --force # optional/pro",
    "wp --path='$WPPath' plugin install wordpress-seo --activate",
    "wp --path='$WPPath' plugin install imagify --activate # or other image optimizer",
    "wp --path='$WPPath' plugin install wp-super-cache --activate # caching (or WP Rocket paid)"
)

if ($DryRun) {
    Write-Info "Dry-run mode. The following commands would be executed:"
    foreach ($c in $commands) { Write-Host "  $c" }
    Write-Info "No changes made. To run, re-run without -DryRun."
    exit 0
}

Write-Info "Executing installation commands..."
foreach ($c in $commands) {
    Write-Info "Running: $c"
    & pwsh -NoProfile -Command $c
    if ($LASTEXITCODE -ne 0) { Write-Warn "Command failed: $c" }
}

Write-Info "Kadence install sequence finished. Check admin UI and configure starter templates as desired."