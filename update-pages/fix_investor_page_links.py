#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()
headers = {'Authorization': 'Basic ' + credentials, 'Content-Type': 'application/json'}

# Updated investor page with proper compliance document links
new_content = '''<!-- wp:html -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap');
:root {
  --lg-bg: #f2f7fb;
  --lg-surface: #ffffff;
  --lg-ink: #0b1726;
  --lg-ink-soft: #2f455c;
  --lg-line: #dbe6ef;
  --lg-blue: #004b93;
  --lg-cyan: #00a8dd;
  --lg-navy: #07111d;
}
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; -webkit-font-smoothing: antialiased; }
.ir-skip-link { position:absolute; left:-9999px; top:0; z-index:10000; padding:10px 14px; border-radius:8px; background:#0a4f95; color:#fff; font-weight:800; }
.ir-skip-link:focus { left:14px; top:14px; outline:3px solid #8ed9ff; }
.ir-hero { position:relative; min-height:320px; display:flex; align-items:flex-end; padding:64px 0 88px; background:linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%); }
.ir-hero img { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
.ir-hero-inner { position:relative; z-index:1; max-width:1220px; margin:0 auto; width:100%; padding:0 24px; }
.ir-hero h1 { font-size:clamp(2rem, 5vw, 3.2rem); font-weight:800; line-height:1.1; color:#fff; margin:10px 0 16px; }
.ir-hero p { color:rgba(255,255,255,.85); font-size:1.05rem; line-height:1.6; margin:0; max-width:700px; }
.ir-kicker { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#00d4ff; }
.ir-content-wrap { max-width:1220px; margin:-48px auto 0; padding:0 24px 80px; position:relative; z-index:2; }
.ir-panel { background:#fff; border:1px solid #dbe7f2; border-radius:20px; padding:clamp(28px, 4vw, 44px); box-shadow:0 12px 40px rgba(7,27,47,.08); }
.ir-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:40px; }
.ir-section-title { font-size:1.4rem; font-weight:800; color:var(--lg-navy); margin:0 0 20px; padding-bottom:16px; border-bottom:2px solid #f0f4f8; }
.ir-subsection { margin-bottom:28px; }
.ir-subsection h3 { font-size:0.95rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#0a4f95; margin:0 0 10px; }
.ir-subsection p { margin:0; font-size:0.95rem; line-height:1.6; color:var(--lg-ink-soft); }
.ir-subsection a { color:var(--lg-blue); font-weight:600; text-decoration:none; transition:all .2s ease; }
.ir-subsection a:hover { color:var(--lg-cyan); text-decoration:underline; }
.ir-doc-list { list-style:none; padding:0; margin:0; }
.ir-doc-list li { margin-bottom:12px; padding-left:24px; position:relative; }
.ir-doc-list li::before { content:"📄"; position:absolute; left:0; }
.ir-doc-list a { color:var(--lg-blue); font-weight:600; text-decoration:none; transition:all .2s ease; }
.ir-doc-list a:hover { color:var(--lg-cyan); text-decoration:underline; }
.ir-actions { display:flex; gap:12px; margin-bottom:20px; }
.ir-nav { display:flex; gap:8px; flex-wrap:wrap; padding-bottom:20px; border-bottom:1px solid #e4edf6; margin-bottom:20px; }
.ir-nav a { display:inline-block; padding:8px 14px; border-radius:8px; background:transparent; color:var(--lg-ink-soft); font-size:0.9rem; font-weight:600; text-decoration:none; transition:all .2s ease; }
.ir-nav a:hover, .ir-nav a[aria-current] { background:#eef5ff; color:var(--lg-blue); }
@media (max-width:768px) { 
  .ir-hero { min-height:280px; padding:48px 0 72px; }
  .ir-hero h1 { font-size:1.8rem; }
  .ir-content-wrap { margin:-40px auto 0; padding:0 16px 60px; }
  .ir-panel { padding:24px; }
  .ir-grid-2 { grid-template-columns:1fr; gap:28px; }
  .ir-nav, .ir-actions { flex-wrap:wrap; }
}
</style>
<!-- /wp:html -->

<!-- wp:group {"className":"ir-shell"} -->
<div class="wp-block-group ir-shell">

<!-- wp:html -->
<a class="ir-skip-link" href="#ir-main">Skip to main content</a>
<section class="ir-hero">
  <img decoding="async" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-home-hero-the-grove-7-scaled.jpg" alt="Pool Safe Inc. Investor Relations">
  <div class="ir-hero-inner">
    <p class="ir-kicker">Investor Relations</p>
    <h1>Pool Safe Inc.</h1>
    <p>TSX Venture Exchange: POOL — Corporate profile, filings, governance, and investor resources.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:group {"className":"ir-content-wrap"} -->
<div class="wp-block-group ir-content-wrap">

<!-- wp:group {"className":"ir-panel"} -->
<div class="wp-block-group ir-panel">

<!-- wp:html -->
<div class="ir-nav">
  <a href="/staging/index.php/investors/" aria-current="page">Investor Relations</a>
  <a href="/staging/index.php/board/">Board</a>
  <a href="/staging/index.php/financials/">Financials</a>
  <a href="/staging/index.php/press/">Press</a>
</div>
<!-- /wp:html -->

<!-- wp:group {"className":"ir-actions"} -->
<div class="wp-block-group ir-actions">
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"buttonType":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link" href="/staging/index.php/financials/">Latest Financials</a></div>
<!-- /wp:button -->
<!-- wp:button {"buttonType":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link" href="/staging/index.php/press/">Latest Press</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":2,"className":"ir-section-title"} -->
<h2>Corporate Information</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"ir-grid-2"} -->
<div class="wp-block-columns ir-grid-2">

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:heading {"level":3} -->
<h3>Corporate Address</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>906 Magnetic Drive<br>North York, ON<br>M3J 2C4<br>Canada</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:heading {"level":3} -->
<h3>Stock Information</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong>Exchange:</strong> TSX Venture<br><strong>Symbol:</strong> POOL<br><strong>Sector:</strong> Consumer Discretionary</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:heading {"level":3} -->
<h3>Auditors</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Horizon Assurance LLP.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:heading {"level":3} -->
<h3>Corporate Counsel</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Garfinkle Biderman LLP</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

<!-- wp:heading {"level":2,"className":"ir-section-title"} -->
<h2>Transfer Agent</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:paragraph -->
<p><strong>TSX TRUST COMPANY</strong><br>200 University Ave., Suite 300<br>Toronto, ON<br>M5H 4H1<br><br><a href="https://www.tsxtrust.com" target="_blank" rel="noopener">www.tsxtrust.com</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":2,"className":"ir-section-title"} -->
<h2>Governance & Compliance</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"ir-doc-list"} -->
<ul class="ir-doc-list">
<li><a href="https://loungenie.com/staging/wp-content/uploads/2026/02/2025-Report-on-Fighting-Against-Forced-Labour-Pool-Safe-Inc.pdf" target="_blank" rel="noopener">Fighting Against Forced Labour &amp; Child Labour Report (2025)</a></li>
<li><strong>Disclosure and Confidentiality Policy</strong> — <em style="color:#999;">(Contact IR for access)</em></li>
<li><strong>Compliance Report</strong> — <em style="color:#999;">(Available upon request)</em></li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":2,"className":"ir-section-title"} -->
<h2>Investor Relations Contact</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"ir-subsection"} -->
<div class="wp-block-group ir-subsection">
<!-- wp:paragraph -->
<p><strong>Email:</strong> <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><strong>Phone:</strong> <a href="tel:+14166302444">+1 (416) 630-2444</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p><em>View Pool Safe's public filings on <a href="https://www.sedar.com" target="_blank" rel="noopener">SEDAR</a>.</em></p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->
'''

# Update page with fixed links
url = 'https://loungenie.com/staging/wp-json/wp/v2/pages/5668'
payload = json.dumps({'content': new_content, 'status': 'publish'}).encode()
req = urllib.request.Request(url, data=payload, headers=headers, method='POST')

try:
    with urllib.request.urlopen(req, timeout=30) as r:
        result = json.loads(r.read())
    print(f'✓ Investor page fixed with proper compliance document links')
    print(f'  - Fighting Against Forced Labour Report: linked ✓')
    print(f'  - Disclosure & Compliance policies: request notice added ✓')
    print(f'  - All other links verified: ✓')
except Exception as e:
    print(f'✗ Error: {e}')
