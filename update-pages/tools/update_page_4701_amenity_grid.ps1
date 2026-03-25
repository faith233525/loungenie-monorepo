<##
.SYNOPSIS
Generate and (optionally) POST a Kadence-based Glassmorphism amenity grid payload
to a WordPress page (ID 4701) via the REST API. Reads credentials from environment vars.

.DESCRIPTION
This script constructs Kadence `rowlayout` markup that implements the 135° Deep Blue
gradient, a Glassmorphic content card, and an asymmetrical amenity grid (icons + text).
By default the script runs in DryRun mode and prints the generated JSON payload.

USAGE
Set the following env vars locally before running (do NOT paste secrets in chat):
$env:WP_USER, $env:WP_PASS, $env:WP_SITE_URL

Example:
  $env:WP_USER = 'Copilot'
  $env:WP_PASS = 'your_app_password_here'
  $env:WP_SITE_URL = 'https://your-staging.example.com'
  pwsh .\tools\update_page_4701_amenity_grid.ps1 -DryRun

##>
param(
    [int]$PageId = 4701,
    [switch]$DryRun,
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

function Build-KadenceAmenityMarkup {
    # Uses Kadence rowlayout + glassmorphic card + asymmetrical grid
    $markup = @'
<!-- wp:kadence/rowlayout {"uniqueID":"hero_amenity","background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":120,"paddingBottom":120} -->
<div class="kt-row-column-wrap">
  <div class="kt-inside-inner-col">
    <!-- wp:heading {"level":1} --><h1 style="color:#ffffff">LounGenie™ — Hospitality Technology for Premium Seating</h1><!-- /wp:heading -->
    <!-- wp:group {"className":"glass-card","style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"30px","right":"30px"}},"color":{"background":"rgba(255,255,255,0.16)"}}} -->
    <div class="glass-card" style="backdrop-filter: blur(15px); border-radius:12px;">
      <!-- wp:kadence/rowlayout {"uniqueID":"amenity_asym","columns":"2","columnControls":{"widths":["60%","40%"]}} -->
      <div class="kt-row-column-wrap">
        <div class="kt-inside-inner-col">
          <!-- Left column: features list -->
          <!-- wp:heading {"level":2,"textAlign":"left","textColor":"white","style":{"typography":{"fontWeight":"700","fontSize":"36px"}}} -->
          <h2 class="has-text-align-left" style="color:#ffffff;font-weight:700;font-size:36px">Smart Cabana Features</h2>
          <!-- /wp:heading -->
          <!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"18px"},"color":{"text":"#e0e0e0"}}} -->
          <p class="has-text-align-left" style="color:#e0e0e0;font-size:18px">Bold Maximalism meets practical poolside tech — QR ordering, waterproof safe, and solar USB charging.</p>
          <!-- /wp:paragraph -->

          <!-- Amenity grid: asymmetric rows -->
          <!-- wp:kadence/rowlayout {"uniqueID":"grid_1","columns":"3","columnControls":{"widths":["33%","33%","34%"]}} -->
          <div class="kt-row-column-wrap" style="margin-top:24px">
            <div class="kt-inside-inner-col">
              <div class="amenity-card">
                <!-- Icon + title + desc -->
                <img src="/wp-content/uploads/icons/qr-ordering.svg" alt="QR Ordering" style="height:56px;margin-bottom:12px">
                <h4 style="color:#ffffff;font-weight:700">QR Ordering</h4>
                <p style="color:#e0e0e0">Scan, order, and print — no POS integration needed.</p>
              </div>
              <div class="amenity-card">
                <img src="/wp-content/uploads/icons/waterproof-safe.svg" alt="Secure Storage" style="height:56px;margin-bottom:12px">
                <h4 style="color:#ffffff;font-weight:700">Secure Storage</h4>
                <p style="color:#e0e0e0">Waterproof safe with keypad — secure and accessible.</p>
              </div>
              <div class="amenity-card">
                <img src="/wp-content/uploads/icons/usb-charge.svg" alt="USB Charging" style="height:56px;margin-bottom:12px">
                <h4 style="color:#ffffff;font-weight:700">USB Charging</h4>
                <p style="color:#e0e0e0">Solar-powered USB ports for guest devices.</p>
              </div>
            </div>
          </div>
          <!-- /wp:kadence/rowlayout -->
        </div>
      </div>
      <!-- /wp:kadence/rowlayout -->
      <!-- Right column: optional image or mockup -->
      <!-- wp:image {"id":0} --><img src="/wp-content/uploads/2025/10/cabana-mockup.png" alt="Cabana mockup" style="max-width:100%;border-radius:8px"><!-- /wp:image -->
    </div>
    <!-- /wp:group -->
  </div>
</div>
<!-- /wp:kadence/rowlayout -->
'@
    return $markup
}

function Build-Payload {
    param([string]$Content)
    return @{ content = $Content }
}

function Trash-DuplicatePages {
    param([int]$TargetId)
    # Simple safety: list pages with same title and trash drafts/duplicates
    try {
        $page = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$TargetId" -Headers @{ Authorization = "Basic $([Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)")))" } -Method Get -ErrorAction Stop
        $title = $page.title.rendered
        $matches = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?search=$([uri]::EscapeDataString($title))&per_page=50" -Headers @{ Authorization = "Basic $([Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)")))" } -Method Get -ErrorAction Stop
        foreach ($m in $matches) {
            if ($m.id -ne $TargetId -and $m.status -ne 'publish') {
                Write-Output "Trashing duplicate/draft page id: $($m.id) title: $($m.title.rendered)"
                Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$($m.id)" -Method Post -Headers @{ Authorization = "Basic $([Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)")))"; 'Content-Type'='application/json' } -Body (@{ status = 'trash' } | ConvertTo-Json) -ErrorAction Stop
            }
        }
    } catch {
        Write-Warning "Cleanup step failed: $($_.Exception.Message)"
    }
}

# Start
if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Error "Please set `WP_SITE_URL`, `WP_USER`, and `WP_PASS` environment variables before running. Exiting."
    exit 1
}

$connected = Test-WPConnection -BaseUrl $env:WP_SITE_URL
if (-not $connected) { exit 1 }

$markup = Build-KadenceAmenityMarkup
$payload = Build-Payload -Content $markup | ConvertTo-Json -Depth 6

Write-Output "Generated payload (truncated preview):"
Write-Output ($markup.Substring(0,[Math]::Min(1200,$markup.Length)))

if ($Cleanup.IsPresent) {
    Write-Output "Running cleanup to trash duplicate drafts..."
    Trash-DuplicatePages -TargetId $PageId
}

if ($DryRun.IsPresent) {
    Write-Output "Dry run enabled — not making REST changes. To apply, run without -DryRun."
    exit 0
}

# Build authorization header
$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))

try {
    Write-Output "Applying payload to page ID $PageId..."
    $uri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$PageId"
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $payload -ErrorAction Stop
    Write-Output "Update successful: page id $($resp.id) status $($resp.status)"
} catch {
    Write-Error "Failed to apply payload: $($_.Exception.Message)"
    exit 1
}
