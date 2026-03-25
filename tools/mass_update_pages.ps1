<##
.SYNOPSIS
Apply prepared Kadence-based content (hero, glass cards, galleries) to multiple pages via REST.

.DESCRIPTION
Maps page IDs to curated Kadence markup (hero, features, gallery, contact) and posts
each payload to the WP REST API. Reads credentials from environment variables.

.USAGE
Set `$env:WP_SITE_URL`, `$env:WP_USER`, `$env:WP_PASS` then run with `-DryRun` first.

##>
param(
    [switch]$DryRun,
    [switch]$Cleanup
)

$pages = @{
    4701 = 'home'
    4862 = 'about'
    2989 = 'product'
    5223 = 'gallery'
    5285 = 'investors'
    5139 = 'contact'
}

function Test-WP {
    param([string]$Base)
    try { Invoke-RestMethod -Uri "$Base/wp-json/" -Method Get -ErrorAction Stop | Out-Null; return $true } catch { Write-Error "WP REST unreachable: $($_.Exception.Message)"; return $false }
}

function Get-MarkupForPage {
    param([string]$kind)
    switch ($kind) {
        'home' {
            return @'
    <!-- Home Hero -->
    <!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":140,"paddingBottom":140} -->
    <div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:heading {"level":1} --><h1 style="color:#ffffff">Luxury Hospitality Reimagined</h1><!-- /wp:heading -->
    <!-- wp:paragraph --><p style="color:#e0e0e0">Premium cabana amenities: QR ordering, waterproof safe, and solar USB charging.</p><!-- /wp:paragraph -->
    <!-- wp:kadence/advancedgallery {"type":"carousel","uniqueID":"home_carousel","carouselAutoPlay":true} -->
    <div class="wp-block-kadence-advancedgallery"><p class="has-text-align-center"><strong>Feature Carousel: Smart Cabana, QR Ordering, Secure Storage</strong></p></div>
    <!-- /wp:kadence/advancedgallery -->

    <!-- wp:kadence/rowlayout {"uniqueID":"home_bento","paddingTop":80,"paddingBottom":80,"background":"#f8f9fa"} -->
    <div class="kt-row-column-wrap"><div class="kt-inside-inner-col">
    <!-- wp:kadence/column {"uniqueID":"_c1_","backgroundColor":"#ffffff","borderRadius":15,"padding":30} --><div class="kt-inside-inner-col"><h3>QR Guest Ordering</h3><p>Boost F&B revenue with digital poolside service — orders print to staff printer.</p></div><!-- /wp:kadence/column -->
    <!-- wp:kadence/column {"uniqueID":"_c2_","backgroundColor":"#ffffff","borderRadius":15,"padding":30} --><div class="kt-inside-inner-col"><h3>Secure Storage</h3><p>Waterproof safe with keypad keeps guest belongings secure.</p></div><!-- /wp:kadence/column -->
    <!-- wp:kadence/column {"uniqueID":"_c3_","backgroundColor":"#ffffff","borderRadius":15,"padding":30} --><div class="kt-inside-inner-col"><h3>Solar USB Charging</h3><p>Solar-powered USB ports for convenient guest charging.</p></div><!-- /wp:kadence/column -->
    </div></div>
    <!-- /wp:kadence/rowlayout -->
    '@
        }
        'about' {
            return @'
<!-- About -->
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":100,"paddingBottom":100} -->
<div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:heading {"level":2} --><h2 style="color:#ffffff">About LounGenie | Hospitality Technology for Premium Seating</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p style="color:#e0e0e0">Built for premium venues. Service-first design and commercial-grade construction. We design, build, and service premium cabana amenities. PoolSafe handles installation, maintenance, and service.</p><!-- /wp:paragraph -->
<!-- wp:group {"className":"about-vision","style":{"spacing":{"padding":{"top":"30px","bottom":"30px"}},"color":{"background":"rgba(255,255,255,0.04)"}}} --><div class="about-vision" style="padding:30px;background:rgba(255,255,255,0.04)"><!-- wp:heading {"level":3} --><h3 style="color:#ffffff">Our Vision</h3><!-- /wp:heading --><!-- wp:paragraph --><p style="color:#e0e0e0">Bring premium hospitality experiences to life with modular, serviceable, and durable amenities that blend into venue aesthetics.</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:image --><img src="/wp-content/uploads/2025/10/team-photo.jpg" alt="Team" style="margin-top:18px;border-radius:8px" /><!-- /wp:image -->
<!-- /wp:kadence/rowlayout -->
'@
        }
        'product' {
                        return @'
<!-- Product -->
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":100,"paddingBottom":100} -->
<div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:heading {"level":2} --><h2 style="color:#ffffff">Product Features</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p style="color:#e0e0e0">Three cumulative tiers: Classic (STASH, CHILL, CHARGE), Service+ (Classic + service call button), and 2.0 (Service+ + QR ordering). Designed for commercial venues with waterproof safe, solar USB charging ports, and removable ice bucket.</p><!-- /wp:paragraph -->
<!-- wp:kadence/column {"uniqueID":"prod_features","columns":"3"} -->
<div class="kt-row-column-wrap" style="margin-top:24px"><div class="kt-inside-inner-col">
    <div class="kt-inside-inner-col"><h4 style="color:#ffffff">STASH — Waterproof Safe</h4><p style="color:#e0e0e0">Waterproof keypad safe for guest valuables.</p></div>
    <div class="kt-inside-inner-col"><h4 style="color:#ffffff">CHARGE — Solar USB</h4><p style="color:#e0e0e0">Solar-powered USB charging ports for guest devices.</p></div>
    <div class="kt-inside-inner-col"><h4 style="color:#ffffff">CHILL — Removable Ice Bucket</h4><p style="color:#e0e0e0">Removable ice bucket for premium service (not insulated).</p></div>
</div></div>
<!-- /wp:kadence/column -->
<!-- wp:image --><img src="/wp-content/uploads/2025/10/product-overview.jpg" alt="Product" style="margin-top:18px;border-radius:8px" /><!-- /wp:image -->
<!-- /wp:kadence/rowlayout -->
'@
        }
        'gallery' {
            return @'
<!-- Gallery -->
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":80,"paddingBottom":80} -->
<div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:gallery --><figure class="wp-block-gallery"><img src="/wp-content/uploads/2025/09/inst1.jpg" alt=""> <img src="/wp-content/uploads/2025/09/inst2.jpg" alt=""> <img src="/wp-content/uploads/2025/09/inst3.jpg" alt=""></figure><!-- /wp:gallery -->
<!-- /wp:kadence/rowlayout -->
'@
        }
        'investors' {
            return @'
<!-- Investors / Financials -->
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":80,"paddingBottom":80} -->
<div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:heading {"level":2} --><h2 style="color:#ffffff">Financials and Filings | Pool Safe Inc. (TSXV: POOL)</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p style="color:#e0e0e0">Key financials and filings for Pool Safe Inc., parent company of LounGenie. For investor materials and filings, request access via investor relations.</p><!-- /wp:paragraph -->
<!-- wp:list --><ul style="color:#e0e0e0"><li>Latest annual report (PDF)</li><li>Quarterly updates</li><li>Corporate presentations</li></ul><!-- /wp:list -->
<!-- wp:image --><img src="/wp-content/uploads/2025/10/investor-graph.jpg" alt="Graph" style="margin-top:18px;border-radius:8px" /><!-- /wp:image -->
<!-- /wp:kadence/rowlayout -->
'@
        }
        'contact' {
                        return @'
<!-- Contact -->
<!-- wp:kadence/rowlayout {"background":"#003366","background2":"#0073e6","backgroundType":"gradient","backgroundGradientAngle":135,"paddingTop":80,"paddingBottom":80} -->
<div class="kt-row-column-wrap"><div class="kt-inside-inner-col"><!-- wp:heading {"level":2} --><h2 style="color:#ffffff">Contact LounGenie</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p style="color:#e0e0e0">For demos, partnerships, or media requests, please reach out. Provide property details and preferred demo windows for fastest scheduling.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p style="color:#e0e0e0"><strong>Sales:</strong> sales@loungenie.com &nbsp; <strong>Press:</strong> press@loungenie.com</p><!-- /wp:paragraph -->
<!-- wp:html --><a href="/staging/contact-loungenie/" class="lg9-btn-primary" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#0073e6;color:#ffffff;text-decoration:none">Request Demo</a><!-- /wp:html -->

<!-- wp:html -->
<!-- HubSpot Form Embed: uses HUBSPOT_PORTAL_ID and HUBSPOT_FORM_ID env vars when available -->
<div id="hubspot-form-container">
    <script src="//js.hsforms.net/forms/v2.js"></script>
    <script>
        (function() {
            var portal = 'YOUR_PORTAL_ID';
            var form = 'YOUR_FORM_ID';
            try {
                var envPortal = '%HUBSPOT_PORTAL_ID%';
                var envForm = '%HUBSPOT_FORM_ID%';
                if (envPortal && envPortal !== '%HUBSPOT_PORTAL_ID%') { portal = envPortal; }
                if (envForm && envForm !== '%HUBSPOT_FORM_ID%') { form = envForm; }
            } catch(e){}
            window._hg_create_hs_form = function(){
                if (portal === 'YOUR_PORTAL_ID' || form === 'YOUR_FORM_ID') {
                    document.getElementById('hubspot-form-container').innerHTML = '<!-- Replace HUBSPOT_PORTAL_ID/HUBSPOT_FORM_ID in script or set env vars -->';
                    return;
                }
                hbspt.forms.create({ region: 'na1', portalId: portal, formId: form, target: '#hubspot-form-container' });
            };
            if (window.hbspt && window.hbspt.forms) { window._hg_create_hs_form(); } else { var s=document.createElement('script'); s.src='//js.hsforms.net/forms/v2.js'; s.onload=window._hg_create_hs_form; document.head.appendChild(s);} 
        })();
    </script>
</div>
<!-- /wp:html -->

<!-- /wp:kadence/rowlayout -->
'@
        }
        default { return "" }
    }
}

if (-not $env:WP_SITE_URL -or -not $env:WP_USER -or -not $env:WP_PASS) { Write-Error "Set WP_SITE_URL, WP_USER, WP_PASS env vars first."; exit 1 }
if (-not (Test-WP -Base $env:WP_SITE_URL)) { exit 1 }

$auth = [Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes("$($env:WP_USER):$($env:WP_PASS)"))

foreach ($id in $pages.Keys) {
    $kind = $pages[$id]
    $content = Get-MarkupForPage -kind $kind
    if (-not $content) { Write-Warning "No content template for $kind; skipping $id"; continue }

    $body = @{ content = $content } | ConvertTo-Json -Depth 8
    Write-Output "`n--- Page $id ($kind) payload preview ---"
    Write-Output ($content.Substring(0,[Math]::Min(800,$content.Length)))

    if ($Cleanup.IsPresent) {
        Write-Output "Running cleanup for page $id..."
        try {
            $page = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$id" -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop
            $title = $page.title.rendered
            $matches = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages?search=$([uri]::EscapeDataString($title))&per_page=50" -Headers @{ Authorization = "Basic $auth" } -Method Get -ErrorAction Stop
            foreach ($m in $matches) { if ($m.id -ne $id -and $m.status -ne 'publish') { Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$($m.id)" -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type'='application/json' } -Body (@{ status='trash' } | ConvertTo-Json) } }
        } catch { Write-Warning ("Cleanup error for {0}: {1}" -f $id, $_.Exception.Message) }
    }

    if ($DryRun.IsPresent) { Write-Output "DryRun: not applying to $id"; continue }

    try {
        Write-Output "Applying to page $id..."
        $resp = Invoke-RestMethod -Uri "$($env:WP_SITE_URL)/wp-json/wp/v2/pages/$id" -Method Post -Headers @{ Authorization = "Basic $auth"; 'Content-Type'='application/json' } -Body $body -ErrorAction Stop
        Write-Output "Updated $id -> status: $($resp.status)"
    } catch { Write-Error ("Failed {0}: {1}" -f $id, $_.Exception.Message) }
}
