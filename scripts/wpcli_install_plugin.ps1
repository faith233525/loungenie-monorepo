param(
  [string]$ZipPath = "dist/lg-block-patterns.zip",
  [string]$WpPath = "~/public_html/staging_loungenie",
  [switch]$Activate
)

Write-Output "WP-CLI plugin installer helper"
if (-not (Test-Path $ZipPath)) {
  Write-Error "ZIP not found at $ZipPath. Provide correct path or upload ZIP to server first."
  exit 1
}

Write-Output "Ensure WP-CLI is installed on the server and run this script on the server's shell (not locally) where the ZIP is accessible."
Write-Output "Example remote commands (run on server):"
Write-Output "  wp plugin install $ZipPath --force --activate"
if ($Activate) {
  Write-Output "Activating plugin..."
  pwsh -Command "wp --path=$WpPath plugin activate lg-block-patterns"
}
