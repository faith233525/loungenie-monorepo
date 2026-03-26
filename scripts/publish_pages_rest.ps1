$ErrorActionPreference = 'Stop'

# Publishes HTML files from content/pages to WordPress via REST API
# Expects environment variables: WP_SITE_URL, WP_REST_USER, WP_REST_PASS

if (-not $env:WP_SITE_URL) { Write-Error "WP_SITE_URL not set"; exit 1 }
if (-not $env:WP_REST_USER) { Write-Error "WP_REST_USER not set"; exit 1 }
if (-not $env:WP_REST_PASS) { Write-Error "WP_REST_PASS not set"; exit 1 }

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

# Accept self-signed certs if present
[System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
[System.Net.ServicePointManager]::Expect100Continue = $false

$base = $env:WP_SITE_URL.TrimEnd('/')
$user = $env:WP_REST_USER
$pass = $env:WP_REST_PASS
$auth = "Basic " + [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$user`:$pass"))
$headers = @{ Authorization = $auth; "Content-Type" = "application/json" }

$pagesDir = Join-Path $PSScriptRoot "..\content\pages"
if (-not (Test-Path $pagesDir)) { Write-Error "Pages dir not found: $pagesDir"; exit 1 }

foreach ($file in Get-ChildItem -Path $pagesDir -Filter *.html) {
    $slug = [System.IO.Path]::GetFileNameWithoutExtension($file.Name) -replace '[^a-z0-9-]','' -replace '\\s+', '-' -replace '_','-' | ForEach-Object { $_.ToLower() }
    $title = ($file.Name -replace '\.html$','') -replace '-',' '
    $content = Get-Content -Raw -Encoding UTF8 -Path $file.FullName

    if ($slug -eq 'home') {
        Write-Host "Skipping home page (managed directly in WordPress): $($file.Name)"
        continue
    }

    Write-Host "Processing $($file.Name) -> slug: $slug"

    # check if page exists by slug
    $checkUrl = "$base/wp-json/wp/v2/pages?slug=$slug"
    try {
        $existing = Invoke-RestMethod -Uri $checkUrl -Method Get -Headers $headers -ErrorAction Stop -TimeoutSec 60
    } catch {
        Write-Host "Warning: could not check existing pages: $_"
        $existing = @()
    }

    $body = @{ title = $title; content = $content; status = 'publish'; slug = $slug } | ConvertTo-Json -Depth 6

    if ($existing -and $existing.Count -gt 0) {
        $id = $existing[0].id
        $url = "$base/wp-json/wp/v2/pages/$id"
        Write-Host "Updating page id $id"
        $resp = Invoke-RestMethod -Uri $url -Method Post -Headers $headers -Body $body -ErrorAction Stop -TimeoutSec 120
        Write-Host "Updated: $($resp.link)"
    } else {
        $url = "$base/wp-json/wp/v2/pages"
        Write-Host "Creating page"
        $resp = Invoke-RestMethod -Uri $url -Method Post -Headers $headers -Body $body -ErrorAction Stop
        Write-Host "Created: $($resp.link)"
    }
}

Write-Host "ALL_DONE"
