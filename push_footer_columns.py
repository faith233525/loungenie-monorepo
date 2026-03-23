import requests, base64

b = 'https://www.loungenie.com/wp-json/wp/v2'
token = 'admin:i6IM cqLZ vQDC pIRk nKFr g35i'
h = {'Authorization': 'Basic ' + base64.b64encode(token.encode()).decode()}

# ── 1. UPDATE HEADER TEMPLATE-PART (CSS change) ────────────────────────────────
r = requests.get(f'{b}/template-parts/twentytwentyfour//header?context=edit', headers=h, timeout=30)
hdr = r.json()
hdr_raw = hdr.get('content', {}).get('raw', '') if isinstance(hdr.get('content'), dict) else hdr.get('content', '')

old_grid_css = '.wp-site-blocks > footer .lg9-footer-grid { max-width:1280px; margin:0 auto; display:grid; grid-template-columns:1.6fr 1fr 1fr; gap:24px; }'
new_grid_css = '.wp-site-blocks > footer .lg9-footer .wp-block-columns { max-width:1280px; margin:0 auto; gap:32px; }\n.wp-site-blocks > footer .lg9-footer .wp-block-column { min-width:0; padding:0 !important; }'

old_resp_css = '.lg9-grid-2, .lg9-grid-3, .lg9-video-grid, .lg9-gallery, .wp-site-blocks > footer .lg9-footer-grid { grid-template-columns:1fr; }'
new_resp_css = '.lg9-grid-2, .lg9-grid-3, .lg9-video-grid, .lg9-gallery { grid-template-columns:1fr; }'

if old_grid_css in hdr_raw:
    hdr_raw = hdr_raw.replace(old_grid_css, new_grid_css)
    print('header: grid_css_replaced=True')
else:
    print('header: grid_css pattern NOT FOUND')

if old_resp_css in hdr_raw:
    hdr_raw = hdr_raw.replace(old_resp_css, new_resp_css)
    print('header: resp_css_replaced=True')
else:
    print('header: resp_css pattern NOT FOUND')

r2 = requests.post(
    f'{b}/template-parts/twentytwentyfour//header',
    headers={**h, 'Content-Type': 'application/json'},
    json={'content': hdr_raw, 'status': 'publish'},
    timeout=30,
)
print(f'header POST status={r2.status_code}')

# ── 2. PUSH NEW FOOTER TEMPLATE-PART ──────────────────────────────────────────
ROOT = 'https://www.loungenie.com'
LOGO = 'https://www.loungenie.com/wp-content/uploads/2025/10/cropped-cropped-LounGenie-Logo.png'

FOOTER = """<!-- wp:group {"align":"full","className":"lg9-prefooter","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull lg9-prefooter"><!-- wp:group {"className":"lg9-prefooter-inner","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
<div class="wp-block-group lg9-prefooter-inner"><!-- wp:group {"className":"lg9-prefooter-copy","layout":{"type":"constrained"}} -->
<div class="wp-block-group lg9-prefooter-copy"><!-- wp:paragraph {"className":"lg9-kicker","style":{"color":{"text":"#9ddfff"},"spacing":{"margin":{"top":"0","bottom":"8px"}}}} -->
<p class="lg9-kicker has-text-color" style="color:#9ddfff;margin-top:0;margin-bottom:8px">Get Started</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"style":{"color":{"text":"#ffffff"},"spacing":{"margin":{"top":"0","bottom":"10px"}},"typography":{"lineHeight":"1.12"}},"fontFamily":"space-grotesk"} -->
<h2 class="wp-block-heading has-space-grotesk-font-family has-text-color" style="color:#ffffff;margin-top:0;margin-bottom:10px;line-height:1.12">Ready to see LounGenie at your property?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"rgba(255,255,255,0.76)"},"spacing":{"margin":{"top":"0","bottom":"0"}},"typography":{"fontSize":"15px","lineHeight":"1.75"}}} -->
<p class="has-text-color" style="color:rgba(255,255,255,0.76);margin-top:0;margin-bottom:0;font-size:15px;line-height:1.75">We'll map the Product to your layout, team, and revenue opportunity in one focused conversation.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"lg9-btn-primary"} -->
<div class="wp-block-button lg9-btn-primary"><a class="wp-block-button__link wp-element-button" href="ROOT/index.php/contact-loungenie/">Request a Demo</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lg9-footer","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull lg9-footer"><!-- wp:columns {"isStackedOnMobile":true} -->
<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex"><!-- wp:column {"width":"44%"} -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:44%"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","width":"180px"} -->
<figure class="wp-block-image size-full is-resized"><a href="ROOT/"><img src="LOGO" alt="LounGenie" style="width:180px"/></a></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>LounGenie is the premium smart cabana Product for hotels, resorts, and waterparks. Ordering, storage, charging, and comfort in one poolside unit.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><li>$0 Upfront</li><li>Revenue Share</li><li>Built in Canada</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Explore</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li><a href="ROOT/">Home</a></li><li><a href="ROOT/index.php/poolside-amenity-unit/">Features</a></li><li><a href="ROOT/index.php/loungenie-videos/">Videos</a></li><li><a href="ROOT/index.php/cabana-installation-photos/">Gallery</a></li><li><a href="ROOT/index.php/hospitality-innovation/">About</a></li><li><a href="ROOT/index.php/contact-loungenie/">Contact</a></li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Investor + Contact</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li><a href="ROOT/index.php/investors/">Investor Relations</a></li><li><a href="ROOT/index.php/board/">Board</a></li><li><a href="ROOT/index.php/financials/">Financials</a></li><li><a href="ROOT/index.php/press/">Press</a></li></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><a href="tel:+14166302444">+1 (416) 630-2444</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="https://www.instagram.com/poolsafeinc/" target="_blank" rel="noopener noreferrer">Instagram</a> | <a href="https://ca.linkedin.com/company/poolsafeinc" target="_blank" rel="noopener noreferrer">LinkedIn</a> | <a href="https://youtube.com/@poolsafeinc?si=r5Qb8P7rphTE83Ms" target="_blank" rel="noopener noreferrer">YouTube</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"className":"lg9-footer-base","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group lg9-footer-base is-content-justification-space-between is-layout-flex wp-block-group-is-layout-flex"><!-- wp:paragraph -->
<p>&copy; 2026 LounGenie / Pool Safe Inc. All rights reserved.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>906 Magnetic Drive, North York, ON M3J 2C4, Canada</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->"""

FOOTER = FOOTER.replace('ROOT', ROOT).replace('LOGO', LOGO)

r3 = requests.post(
    f'{b}/template-parts/twentytwentyfour//footer',
    headers={**h, 'Content-Type': 'application/json'},
    json={'content': FOOTER, 'status': 'publish'},
    timeout=30,
)
print(f'footer POST status={r3.status_code}')
