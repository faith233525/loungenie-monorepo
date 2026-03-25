<##
.SYNOPSIS
Create and (optionally) apply a Full-Page Kadence-based Master Template
to multiple WordPress pages via the REST API. By default this script runs in DryRun
mode and prints previews; pass -Apply to perform live updates (status: publish).

.DESCRIPTION
This script builds a complete page payload (Hero, Carousel, Bento Grid, header/footer)
using Kadence rowlayout blocks and updates the list of Target IDs. It also includes
logic to trash auto-drafts and pending pages to shrink noisy navigation menus.

USAGE
Set environment variables before running (do NOT paste secrets in chat):
$env:WP_SITE_URL, $env:WP_USER, $env:WP_PASS
Dry run (preview only):
  pwsh .\tools\overwrite_pages_master_template.ps1 -DryRun
Apply live (updates pages and sets status=publish):
  pwsh .\tools\overwrite_pages_master_template.ps1 -Apply
##>

param(
    [switch]$DryRun,
    [switch]$Apply
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

function Build-MasterTemplate {
    param([int]$PageId)
    # Master Kadence template: Hero, Feature Carousel, Bento Grid, Footer
    $markup = @'
<!-- wp:group {"align":"full","className":"lg-master-shell"} -->
<div class="lg-master-shell">
  <!-- Global header block (inline safe header) -->
  <!-- wp:group {"className":"lg-global-header"} -->
  <div class="lg-global-header" style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:linear-gradient(90deg,#041224,#0a315f);">
    <div class="lg-logo" style="flex:0 0 auto;"><img src="/wp-content/uploads/2026/03/lg-logo-white.png" alt="LounGenie logo" style="height:44px;"/></div>
    <nav class="lg-nav" style="flex:1 1 auto;text-align:center;font-weight:800;color:#fff;">
      <a href="/staging/" style="color:#fff;margin:0 14px;">Products</a>
      <a href="/staging/cabana-installation-photos/" style="color:#fff;margin:0 14px;">Gallery</a>
      <a href="/staging/investors/" style="color:#fff;margin:0 14px;">Investors</a>
      <a href="/staging/contact/" style="color:#fff;margin:0 14px;">Contact</a>
    </nav>
    <div class="lg-cta" style="flex:0 0 auto;"><a href="/staging/contact/" style="background:linear-gradient(135deg,#0050a8,#00a9dd);padding:10px 14px;border-radius:10px;color:#fff;font-weight:800;">Request Demo</a></div>
  </div>
  <!-- /wp:group -->

  <!-- Hero: Deep Blue gradient, bold title -->
  <!-- wp:kadence/rowlayout {"uniqueID":"master_hero_$PageId","background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":120,"paddingBottom":120} -->
  <div class="kt-row-column-wrap">
    <div class="kt-inside-inner-col">
      <!-- wp:heading {"level":1} --><h1 style="color:#ffffff;">LounGenie — Hospitality Technology for Premium Seating</h1><!-- /wp:heading -->
      <!-- wp:paragraph --><p style="color:#e0e0e0;max-width:820px;">High-energy, guest-first amenity solutions: waterproof safe, solar USB charging, QR ordering, and service call support — designed for cabanas, daybeds and premium seating.</p><!-- /wp:paragraph -->
    </div>
  </div>
  <!-- /wp:kadence/rowlayout -->

  <!-- Feature Carousel (static fallback markup using Kadence/columns) -->
  <!-- wp:kadence/rowlayout {"uniqueID":"master_carousel_$PageId","paddingTop":60,"paddingBottom":60} -->
  <div class="kt-row-column-wrap">
    <div class="kt-inside-inner-col">
      <!-- Carousel: three feature cards -->
      <div style="display:flex;gap:18px;flex-wrap:wrap;justify-content:center;">
        <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:18px;min-width:260px;max-width:360px;text-align:left;">
          <h3 style="color:#fff;margin-top:0;">QR Ordering</h3>
          <p style="color:#e0e0e0">Guests scan a QR and orders print on the dedicated staff printer — no POS integration required.</p>
        </div>
        <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:18px;min-width:260px;max-width:360px;text-align:left;">
          <h3 style="color:#fff;margin-top:0;">Waterproof Safe</h3>
          <p style="color:#e0e0e0">Waterproof safe with keypad for guest valuables — secure and accessible.</p>
        </div>
        <div style="background:rgba(255,255,255,0.06);border-radius:14px;padding:18px;min-width:260px;max-width:360px;text-align:left;">
          <h3 style="color:#fff;margin-top:0;">Solar USB Charging</h3>
          <p style="color:#e0e0e0">Integrated solar USB ports to keep guests powered throughout the day.</p>
        </div>
      </div>
    </div>
  </div>
  <!-- /wp:kadence/rowlayout -->

  <!-- Bento Grid: Asymmetrical tech specs -->
  <!-- wp:kadence/rowlayout {"uniqueID":"master_bento_$PageId","paddingTop":80,"paddingBottom":80} -->
  <div class="kt-row-column-wrap">
    <div class="kt-inside-inner-col">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
        <div style="background:rgba(255,255,255,0.06);padding:20px;border-radius:12px;color:#fff;">
          <h4 style="margin:0 0 8px;">Dimensions</h4>
          <p style="color:#e0e0e0;margin:0;">Custom-fit to seat pockets and cabana rails.</p>
        </div>
        <div style="grid-column:span 2;background:rgba(255,255,255,0.06);padding:20px;border-radius:12px;color:#fff;">
          <h4 style="margin:0 0 8px;">Features</h4>
          <ul style="color:#e0e0e0;margin:0;padding-left:18px;"><li>QR Ordering (prints to staff printer)</li><li>Waterproof safe</li><li>Solar USB charging</li></ul>
        </div>
        <div style="background:rgba(255,255,255,0.06);padding:20px;border-radius:12px;color:#fff;">
          <h4 style="margin:0 0 8px;">Materials</h4>
          <p style="color:#e0e0e0;margin:0;">Commercial-grade aluminum interior; waterproof keypad.</p>
        </div>
      </div>
    </div>
  </div>
  <!-- /wp:kadence/rowlayout -->

  <!-- Footer: 4-column corporate footer with TSXV ticker -->
  <!-- wp:group {"className":"lg-master-footer"} -->
  <div class="lg-master-footer" style="background:linear-gradient(90deg,#081624,#0f4a86);color:#fff;padding:44px 24px;margin-top:32px;border-top:1px solid rgba(255,255,255,0.06);">
    <div style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
      <div>
        <h4 style="margin:0 0 8px;">Product</h4>
        <ul style="list-style:none;padding:0;margin:0;color:#e0e0e0;"><li>Cabana Units</li><li>Service+</li></ul>
      </div>
      <div>
        <h4 style="margin:0 0 8px;">Company</h4>
        <ul style="list-style:none;padding:0;margin:0;color:#e0e0e0;"><li>About</li><li>Careers</li></ul>
      </div>
      <div>
        <h4 style="margin:0 0 8px;">Resources</h4>
        <ul style="list-style:none;padding:0;margin:0;color:#e0e0e0;"><li>Gallery</li><li>Press</li></ul>
      </div>
      <div>
        <h4 style="margin:0 0 8px;">Investor</h4>
        <p style="color:#e0e0e0;margin:0;">TSXV: POOL</p>
      </div>
    </div>
  </div>
  <!-- /wp:group -->

</div>
<!-- /wp:group -->
'@
    return $markup
}

function Trash-AutoDraftsAndPending {
    param()
    try {
        $auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
        $statuses = @('auto-draft','draft','pending')
        foreach ($s in $statuses) {
            $uri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?status=$s&per_page=50"
            $matches = Invoke-RestMethod -Uri $uri -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop
            foreach ($m in $matches) {
                try {
                    Write-Output "Trashing page id $($m.id) status $s title: $($m.title.rendered)"
                    Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$($m.id)" -Headers @{ Authorization = "Basic $auth"; 'Content-Type'='application/json' } -Method Post -Body (@{ status = 'trash' } | ConvertTo-Json) -ErrorAction Stop
                } catch {
                    Write-Warning "Failed to trash $($m.id): $($_.Exception.Message)"
                }
            }
        }
    } catch {
        Write-Warning "Cleanup failed: $($_.Exception.Message)"
    }
}

function Update-Page {
    param([int]$Id, [string]$Content, [switch]$Perform)
    $payload = @{ content = $Content; status = 'publish' } | ConvertTo-Json -Depth 12
    $auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))
    $uri = "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$Id"
    if ($Perform) {
        try {
            Write-Output "Applying to page $Id..."
            $resp = Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type' = 'application/json' } -Body $payload -ErrorAction Stop
            Write-Output "Updated $Id -> status: $($resp.status) id: $($resp.id)"
        } catch {
          Write-Error ("Failed to update {0}: {1}" -f $Id, $_.Exception.Message)
        }
    } else {
        Write-Output "DryRun: preview for page $Id (truncated):"
        Write-Output ($Content.Substring(0, [Math]::Min(1200, $Content.Length)))
    }
}

# Main
if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) {
    Write-Error "Please set WP_SITE_URL, WP_USER, and WP_PASS environment variables. Exiting."
    exit 1
}

$connected = Test-WPConnection -BaseUrl $env:WP_SITE_URL
if (-not $connected) { exit 1 }

# Target marketing pages
$TargetIds = @(4701,4862,2989,5223,5285,5139)

if ($Apply) {
    Write-Output "Apply requested: will update pages and set status=publish. Running cleanup to trash auto-drafts/pending."
    Trash-AutoDraftsAndPending
}

foreach ($id in $TargetIds) {
    $content = Build-MasterTemplate -PageId $id
    if ($Apply) {
        Update-Page -Id $id -Content $content -Perform
    } else {
        Update-Page -Id $id -Content $content
    }
}

if ($DryRun -and -not $Apply) {
    Write-Output "Dry run complete. To apply changes run with -Apply (will trash auto-drafts/pending and publish pages)."
}
