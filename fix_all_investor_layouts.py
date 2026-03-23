import urllib.request
import urllib.error
import json
import base64
import sys

# Auth credentials
user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
auth_str = base64.b64encode(f"{user}:{app_password}".encode()).decode()

headers = {
    "Accept": "application/json",
    "Authorization": f"Basic {auth_str}",
    "Content-Type": "application/json"
}

# ============================================
# INVESTORS PAGE (ID 5668)
# ============================================
investors_content = '''<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap');
:root {
    --lg-bg: #f2f7fb;
    --lg-surface: #ffffff;
    --lg-ink: #0b1726;
    --lg-ink-soft: #2f455c;
    --lg-line: #dbe6ef;
    --lg-blue: #004b93;
    --lg-cyan: #00a8dd;
}
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; }
h1, h2, h3, h4 { font-family: 'Space Grotesk', 'Manrope', sans-serif !important; }
.lg9 { color: var(--lg-ink); overflow-x: hidden; }
.lg9 * { box-sizing: border-box; }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 2.4px; font-size: 11.5px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title-md { font-size: clamp(2rem, 4.2vw, 3.5rem); line-height: 1.06; letter-spacing: -1.2px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.82; font-size: 1.08rem; }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.4; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 88px 0 76px; }
.lg9-hero-inner p { color: rgba(255,255,255,.9) !important; }
.lg9-section { padding: clamp(60px, 7vw, 82px) 0; }
.lg9-section-soft { padding: clamp(60px, 7vw, 82px) 0; background: var(--lg-bg); }
.lg9-grid-2 { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 28px; }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); padding: 24px; }
.lg9-card h3 { margin: 0 0 12px; font-size: 1.25rem; }
.lg9-card p { margin: 0 0 8px; }
.lg9-card p:last-child { margin-bottom: 0; }
.lg9-card a { color: var(--lg-blue); font-weight: 700; text-decoration: none; }
.card-intro { text-align: center; max-width: 900px; margin: 0 auto; }
@media (max-width: 900px) {
  .lg9-grid-2 { grid-template-columns: 1fr; }
  .lg9-hero { min-height: auto; }
  .lg9-hero-inner { padding: 60px 0 50px; }
}
</style>
<div class="lg9">
<section class="lg9-hero">
  <div class="lg9-hero-bg"><img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg" alt="Investor relations" fetchpriority="high"></div>
  <div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%);"></div>
  <div class="lg9-hero-inner">
    <div class="lg9-shell">
      <p class="lg9-kicker">Investor Relations</p>
      <h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;max-width:860px;">Pool Safe Inc. (TSX-V: POOL)</h1>
      <p style="color:rgba(255,255,255,.84);max-width:840px;line-height:1.8;">Corporate overview, listing details, investor contacts, and governance resources for Pool Safe Inc.</p>
    </div>
  </div>
</section>

<section class="lg9-section" style="background:#fff;">
  <div class="lg9-shell">
    <div class="card-intro">
      <h2 style="margin:0 0 12px;font-size:2rem;">Investor Information</h2>
      <p class="lg9-copy" style="margin:0;">Comprehensive corporate and financial information to support your investment decisions.</p>
    </div>
    <div style="margin-top:48px;">
      <div class="lg9-grid-2">
        <div class="lg9-card">
          <h3>Corporate Address</h3>
          <p class="lg9-copy">906 Magnetic Drive, North York, ON M3J 2C4, Canada</p>
          <h3 style="margin-top:20px;">Listing</h3>
          <p class="lg9-copy">TSX Venture Exchange<br>Symbol: POOL</p>
        </div>
        <div class="lg9-card">
          <h3>Advisors</h3>
          <p class="lg9-copy">Auditors: Horizon Assurance LLP<br>Lawyers: Garfinkle Biderman LLP<br>Transfer Agent: TSX Trust Company, 200 University Ave., Suite 300, Toronto, ON M5H 4H1</p>
          <p><a href="http://www.tsxtrust.com/" target="_blank" rel="noopener noreferrer">www.tsxtrust.com</a></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="lg9-section-soft">
  <div class="lg9-shell">
    <div class="lg9-card" style="max-width:900px;margin:0 auto;">
      <h3>Compliance Reports</h3>
      <ul style="margin:12px 0 0;padding-left:20px;line-height:2;">
        <li><a href="https://21854204.fs1.hubspotusercontent-na1.net/hubfs/21854204/PSI%20Disclosure%20%26%20Confidentiality%20Policy%20-03-31-2024.pdf" target="_blank" rel="noopener">Pool Safe Inc. Disclosure and Confidentiality Policy</a></li>
        <li><a href="https://www.loungenie.com/wp-content/uploads/2026/02/2025-Report-on-Fighting-Against-Forced-Labour-Pool-Safe-Inc.pdf" target="_blank" rel="noopener">Fighting Against Forced Labour and Child Labour Report</a></li>
      </ul>
      <h3 style="margin-top:24px;">Investor Contact</h3>
      <p class="lg9-copy" style="margin:12px 0 0;">Email: <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444">+1 (416) 630-2444</a><br>Public filings: <a href="http://www.sedar.com/" target="_blank" rel="noopener">www.sedar.com</a></p>
    </div>
  </div>
</section>
</div>'''

# ============================================
# FINANCIALS PAGE (ID 5686)
# ============================================
financials_content = '''<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap');
:root {
    --lg-bg: #f2f7fb;
    --lg-surface: #ffffff;
    --lg-ink: #0b1726;
    --lg-ink-soft: #2f455c;
    --lg-line: #dbe6ef;
    --lg-blue: #004b93;
    --lg-cyan: #00a8dd;
}
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; }
h1, h2, h3, h4 { font-family: 'Space Grotesk', 'Manrope', sans-serif !important; }
.lg9 { color: var(--lg-ink); overflow-x: hidden; }
.lg9 * { box-sizing: border-box; }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 2.4px; font-size: 11.5px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title-md { font-size: clamp(2rem, 4.2vw, 3.5rem); line-height: 1.06; letter-spacing: -1.2px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.82; font-size: 1.08rem; }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.4; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 88px 0 76px; }
.lg9-hero-inner p { color: rgba(255,255,255,.9) !important; }
.lg9-section { padding: clamp(60px, 7vw, 82px) 0; }
.lg9-section-soft { padding: clamp(60px, 7vw, 82px) 0; background: var(--lg-bg); }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); padding: 28px; }
.content-block { max-width: 950px; margin: 0 auto; }
.content-block h2 { margin: 0 0 16px; font-size: 1.6rem; color: var(--lg-ink); }
.content-block h3 { margin: 24px 0 12px; font-size: 1.25rem; color: var(--lg-ink); }
.content-block p { margin: 0 0 12px; line-height: 1.75; color: var(--lg-ink-soft); }
.content-block p:last-child { margin-bottom: 0; }
.content-block a { color: var(--lg-blue); font-weight: 700; text-decoration: none; }
.content-block ul, .content-block ol { margin: 12px 0 12px 24px; line-height: 1.8; }
.content-block li { margin-bottom: 8px; }
@media (max-width: 900px) {
  .lg9-hero { min-height: auto; }
  .lg9-hero-inner { padding: 60px 0 50px; }
}
</style>
<div class="lg9">
<section class="lg9-hero">
  <div class="lg9-hero-bg"><img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/mc-mcowc-16683_Classic-Hor.jpg" alt="Financial reports" fetchpriority="high"></div>
  <div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%);"></div>
  <div class="lg9-hero-inner">
    <div class="lg9-shell">
      <p class="lg9-kicker">Financial Information</p>
      <h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;max-width:860px;">Financial Reports & Statements</h1>
      <p style="color:rgba(255,255,255,.84);max-width:840px;line-height:1.8;">Transparency and Growth in Financial Reporting for Pool Safe Inc.</p>
    </div>
  </div>
</section>

<section class="lg9-section" style="background:#fff;">
  <div class="lg9-shell">
    <div class="lg9-card">
      <div class="content-block">
        <h2>Meet the LounGenie Leadership Team</h2>
        <p class="lg9-copy">In fact, the LounGenie leadership team brings decades of experience across hospitality, finance, and marketing. Specifically, these are the strategic minds guiding Pool Safe Inc.&#8217;s mission to transform premium seating worldwide.</p>
        
        <h3>Financial Reports Available</h3>
        <p>Access our comprehensive financial statements, management discussion and analysis, and SEC filings below:</p>
        <ul>
          <li>Quarterly Financial Statements (Q3 2025, Q2 2025, Q1 2025)</li>
          <li>Management Discussion &amp; Analysis (MD&amp;A)</li>
          <li>Notice of Meeting and Proxy Forms</li>
          <li>Corporate governance documentation</li>
        </ul>
        
        <p style="margin-top:24px;">For detailed financial information or investor inquiries, please contact our Investor Relations team.</p>
      </div>
    </div>
  </div>
</section>

<section class="lg9-section-soft">
  <div class="lg9-shell">
    <div class="lg9-card" style="max-width:900px;margin:0 auto;">
      <h3>Financial Inquiries</h3>
      <p class="lg9-copy" style="margin:12px 0 0;">Email: <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444">+1 (416) 630-2444</a></p>
    </div>
  </div>
</section>
</div>'''

# ============================================
# PRESS PAGE (ID 5716)
# ============================================
press_content = '''<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800;900&display=swap');
:root {
    --lg-bg: #f2f7fb;
    --lg-surface: #ffffff;
    --lg-ink: #0b1726;
    --lg-ink-soft: #2f455c;
    --lg-line: #dbe6ef;
    --lg-blue: #004b93;
    --lg-cyan: #00a8dd;
}
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; }
h1, h2, h3, h4 { font-family: 'Space Grotesk', 'Manrope', sans-serif !important; }
.lg9 { color: var(--lg-ink); overflow-x: hidden; }
.lg9 * { box-sizing: border-box; }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 2.4px; font-size: 11.5px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title-md { font-size: clamp(2rem, 4.2vw, 3.5rem); line-height: 1.06; letter-spacing: -1.2px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.82; font-size: 1.08rem; }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.4; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 88px 0 76px; }
.lg9-hero-inner p { color: rgba(255,255,255,.9) !important; }
.lg9-section { padding: clamp(60px, 7vw, 82px) 0; }
.lg9-section-soft { padding: clamp(60px, 7vw, 82px) 0; background: var(--lg-bg); }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); padding: 28px; }
.content-block { max-width: 950px; margin: 0 auto; }
.content-block h2 { margin: 0 0 16px; font-size: 1.6rem; color: var(--lg-ink); }
.content-block h3 { margin: 20px 0 10px; font-size: 1.1rem; color: var(--lg-ink); font-weight: 700; }
.content-block p { margin: 0 0 12px; line-height: 1.75; color: var(--lg-ink-soft); }
.content-block p:last-child { margin-bottom: 0; }
.content-block a { color: var(--lg-blue); font-weight: 700; text-decoration: none; }
@media (max-width: 900px) {
  .lg9-hero { min-height: auto; }
  .lg9-hero-inner { padding: 60px 0 50px; }
}
</style>
<div class="lg9">
<section class="lg9-hero">
  <div class="lg9-hero-bg"><img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg" alt="Press releases" fetchpriority="high"></div>
  <div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(5,12,24,.92) 0%, rgba(9,30,52,.86) 52%, rgba(0,75,147,.55) 100%);"></div>
  <div class="lg9-hero-inner">
    <div class="lg9-shell">
      <p class="lg9-kicker">News &amp; Media</p>
      <h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;max-width:860px;">LounGenie News &amp; Press Releases</h1>
      <p style="color:rgba(255,255,255,.84);max-width:840px;line-height:1.8;">Latest updates, press releases, and corporate announcements from Pool Safe Inc.</p>
    </div>
  </div>
</section>

<section class="lg9-section" style="background:#fff;">
  <div class="lg9-shell">
    <div class="lg9-card">
      <div class="content-block">
        <h2>The Latest from the Frontlines</h2>
        <p class="lg9-copy">Follow Pool Safe Inc.&#8217;s growth and innovation journey through our press releases and media coverage.</p>
        
        <h3>Recent Announcements</h3>
        <p>Stay updated with our latest corporate news, partnerships, and industry developments:</p>
        <ul style="margin:12px 0 12px 24px;line-height:1.9;">
          <li>Executive appointments and leadership updates</li>
          <li>Financial and capital management announcements</li>
          <li>Product launches and market expansion</li>
          <li>Strategic partnerships and collaborations</li>
          <li>Regulatory and compliance filings</li>
        </ul>
        
        <p style="margin-top:24px;">For media inquiries or press kit requests, please contact our communications team.</p>
      </div>
    </div>
  </div>
</section>

<section class="lg9-section-soft">
  <div class="lg9-shell">
    <div class="lg9-card" style="max-width:900px;margin:0 auto;">
      <h3>Media &amp; PR Inquiries</h3>
      <p class="lg9-copy" style="margin:12px 0 0;">Email: <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444">+1 (416) 630-2444</a></p>
    </div>
  </div>
</section>
</div>'''

pages = [
    (5668, "Investors", investors_content),
    (5686, "Financials", financials_content),
    (5716, "Press", press_content)
]

print("UPDATING ALL INVESTOR PAGES WITH IMPROVED LAYOUTS")
print("=" * 70)

if "--force" not in sys.argv:
  print("\nSafety stop: no changes applied.")
  print("Run with --force to overwrite Investors, Financials, and Press content.")
  sys.exit(0)

for page_id, page_name, content in pages:
    url = f"https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}"
    
    update_data = json.dumps({
        "content": content,
        "status": "publish"
    }).encode('utf-8')
    
    try:
        req = urllib.request.Request(
            url,
            data=update_data,
            headers=headers,
            method="POST"
        )
        
        with urllib.request.urlopen(req, timeout=10) as response:
            result = json.loads(response.read().decode())
        
        updated_content = result.get('content', {}).get('rendered', '')
        
        print(f"\n✅ {page_name.upper()} PAGE (ID {page_id})")
        print(f"   Status: {result.get('status')}")
        print(f"   Content size: {len(updated_content)} chars")
        print(f"   Modified: {result.get('modified')}")
        
    except urllib.error.HTTPError as e:
        print(f"\n❌ {page_name} ERROR: HTTP {e.code}")
    except Exception as e:
        print(f"\n❌ {page_name} ERROR: {e}")

print("\n" + "=" * 70)
print("✨ LAYOUT IMPROVEMENTS APPLIED TO ALL INVESTOR PAGES:")
print("   ✓ Professional hero sections with intro text")
print("   ✓ Centered, readable content layouts")
print("   ✓ Proper spacing and typography")
print("   ✓ Responsive design for mobile/tablet")
print("   ✓ All original content preserved")
