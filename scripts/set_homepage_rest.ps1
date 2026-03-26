$ErrorActionPreference = 'Stop'

# Sets the front page on a WordPress site via REST API.
# Expects environment variables: WP_SITE_URL, WP_REST_USER, WP_REST_PASS

if (-not $env:WP_SITE_URL) { Write-Error "WP_SITE_URL not set"; exit 1 }
if (-not $env:WP_REST_USER) { Write-Error "WP_REST_USER not set"; exit 1 }
if (-not $env:WP_REST_PASS) { Write-Error "WP_REST_PASS not set"; exit 1 }

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
[System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
[System.Net.ServicePointManager]::Expect100Continue = $false

$base = $env:WP_SITE_URL.TrimEnd('/')
$user = $env:WP_REST_USER
$pass = $env:WP_REST_PASS
$auth = "Basic " + [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$user`:$pass"))
$headers = @{ Authorization = $auth; "Content-Type" = "application/json" }

# Find the home page by slug
$slug = 'home'
$checkUrl = "$base/wp-json/wp/v2/pages?slug=$slug"
Write-Host "Looking up page with slug '$slug' at $checkUrl"

$existing = Invoke-RestMethod -Uri $checkUrl -Method Get -Headers $headers -ErrorAction Stop -TimeoutSec 60
if (-not $existing -or $existing.Count -eq 0) {
    Write-Error "No page found with slug '$slug'"; exit 1
}

$id = $existing[0].id
Write-Host "Found home page id: $id"

# Update settings to use this page as the front page
$settingsUrl = "$base/wp-json/wp/v2/settings"
$body = @{ show_on_front = 'page'; page_on_front = $id } | ConvertTo-Json -Depth 3

Write-Host "Setting show_on_front=page, page_on_front=$id via $settingsUrl"
$resp = Invoke-RestMethod -Uri $settingsUrl -Method Post -Headers $headers -Body $body -ErrorAction Stop -TimeoutSec 60

Write-Host "Updated settings: show_on_front=$($resp.show_on_front); page_on_front=$($resp.page_on_front)"
Write-Host "ALL_DONE"
