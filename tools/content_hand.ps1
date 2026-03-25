<##
.SYNOPSIS
Automates applying a master content payload to target marketing pages, trashes drafts, and triggers the Theme Brain purge endpoint.

.DESCRIPTION
This script defaults to a DryRun. Use -Apply to perform updates and trigger the purge endpoint. Credentials are read from environment variables `WP_SITE_URL`, `WP_USER`, `WP_PASS`.
##>

param(
    [switch]$DryRun,
    [switch]$Apply,
    [switch]$Cleanup
)

function Test-WPConnection {
    param([string]$BaseUrl)
    try {
        $resp = Invoke-RestMethod -Uri "$BaseUrl/wp-json/" -Method Get -ErrorAction Stop
        Write-Output "WP REST OK: $($resp.name)"
        return $true
    } catch {
        Write-Error "WP REST check failed: $($_.Exception.Message)"
        return $false
    }
}

function Build-Markup {
    $m = @'
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":100,"paddingBottom":100} -->
<div class="kt-row-column-wrap"><h1 style="color:#ffffff">Luxury Hospitality Reimagined</h1></div><!-- /wp:kadence/rowlayout -->
<!-- wp:kadence/advancedgallery {"type":"carousel","imageRatio":"16:9","carouselAutoPlay":true} -->
<div class="wp-block-kadence-advancedgallery"><p><strong>[Amenity Carousel Active]</strong></p></div>
<!-- /wp:kadence/advancedgallery -->
<!-- wp:kadence/rowlayout {"paddingTop":60,"paddingBottom":60,"background":"#f8f9fa"} --><div class="kt-row-column-wrap">
<!-- wp:kadence/column {"backgroundColor":"#ffffff","borderRadius":15,"padding":30} --><h3>QR Guest Ordering</h3><p>Increase poolside revenue by 30%.</p><!-- /wp:kadence/column -->
<!-- wp:kadence/column {"backgroundColor":"#ffffff","borderRadius":15,"padding":30} --><h3>Secure Storage</h3><p>Waterproof digital safes for guest peace of mind.</p><!-- /wp:kadence/column -->
</div><!-- /wp:kadence/rowlayout -->
'@
    return $m
}

if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Error "Please set WP_SITE_URL, WP_USER, and WP_PASS environment variables. Exiting."
    exit 1
}

$connected = Test-WPConnection -BaseUrl $env:WP_SITE_URL
if (-not $connected) { exit 1 }

$targetIDs = @(4701, 4862, 2989, 5223, 5285, 5139)
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
$markup = Build-Markup

if (-not $Apply.IsPresent) {
    Write-Output "Dry run mode (no remote changes). Use -Apply to perform updates and purge."
    foreach ($id in $targetIDs) {
        Write-Output "--- Preview for Page $id (truncated) ---"
        Write-Output ($markup.Substring(0,[Math]::Min(1000,$markup.Length)))
    }
    Write-Output "DryRun complete."
    exit 0
}

foreach ($id in $targetIDs) {
    try {
        Write-Host "🚀 Applying content to page $id..." -ForegroundColor Cyan
        $uri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$id"
        $body = @{ content = $markup; status = 'publish' } | ConvertTo-Json -Depth 8
        $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $body -ErrorAction Stop
        Write-Output "Updated $($resp.id) -> status: $($resp.status)"
    } catch {
        Write-Warning ("Failed to update page {0}: {1}" -f $id, $_.Exception.Message)
    }
}

if ($Cleanup.IsPresent) {
    try {
        Write-Host "🧹 Trashing header clutter (drafts)..." -ForegroundColor Yellow
        $drafts = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?status=draft&per_page=100" -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop
        foreach ($d in $drafts) {
            Write-Output "Deleting draft id: $($d.id) title: $($d.title.rendered)"
            Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$($d.id)?force=true" -Method Delete -Headers @{ Authorization = "Basic $auth" } -ErrorAction Stop
        }
    } catch {
        Write-Warning ("Cleanup failed: {0}" -f $_.Exception.Message)
    }
}

try {
    Write-Host "❄️ Triggering Auto-Purge via Theme Brain endpoint..." -ForegroundColor Green
    $purgeUri = "$($env:WP_SITE_URL)/wp-json/loungenie/v1/purge"
    $p = Invoke-RestMethod -Uri $purgeUri -Method Post -ErrorAction Stop
    Write-Output "Purge response: $p"
} catch {
    Write-Warning ("Purge endpoint failed or not present: {0}" -f $_.Exception.Message)
}

Write-Host "✅ Content sync complete." -ForegroundColor Green
