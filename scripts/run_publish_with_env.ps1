$env:WP_SITE_URL = 'https://loungenie.com/staging'
$env:WP_REST_USER = 'copilot'
$env:WP_REST_PASS = 'Ozw0 HyMh hL5v xYSk teA9 Rj73'

Write-Host "Running publish_pages_rest.ps1 with WP_SITE_URL=$env:WP_SITE_URL"
& "$PSScriptRoot\publish_pages_rest.ps1"
