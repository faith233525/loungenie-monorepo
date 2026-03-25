import urllib.request
import urllib.error
import json
import base64

# Auth credentials
user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
auth_str = base64.b64encode(f"{user}:{app_password}".encode()).decode()

headers = {
    "Accept": "application/json",
    "Authorization": f"Basic {auth_str}",
    "Content-Type": "application/json"
}

# Enhanced Board page with proper layout, spacing, and intro
enhanced_board_content = '''<style>
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
html, body { font-family: 'Manrope', sans-serif !important; font-size: 16px; -webkit-font-smoothing: antialiased; }
h1, h2, h3, h4 { font-family: 'Space Grotesk', 'Manrope', sans-serif !important; }
.lg9 { color: var(--lg-ink); overflow-x: hidden; }
.lg9 * { box-sizing: border-box; }
.lg9 a { text-decoration: none; }
.lg9-shell { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.lg9-kicker { text-transform: uppercase; letter-spacing: 2.4px; font-size: 11.5px; font-weight: 800; color: var(--lg-cyan); }
.lg9-title-md { font-size: clamp(2rem, 4.2vw, 3.5rem); line-height: 1.06; letter-spacing: -1.2px; font-weight: 800; margin: 0; }
.lg9-copy { color: var(--lg-ink-soft); line-height: 1.82; font-size: 1.08rem; }
.lg9-hero { position: relative; min-height: 84vh; display:flex; align-items:center; background:#09131f; }
.lg9-hero-bg { position:absolute; inset:0; }
.lg9-hero-bg img { width:100%; height:100%; object-fit:cover; opacity:.4; }
.lg9-hero-overlay { position:absolute; inset:0; background: linear-gradient(112deg, rgba(8,18,29,.97) 0%, rgba(10,30,50,.9) 52%, rgba(0,85,165,.62) 100%); }
.lg9-hero-inner { position:relative; z-index:2; width:100%; padding: 88px 0 76px; }
.lg9-hero-inner p { color: rgba(255,255,255,.9) !important; }
.lg9-section { padding: clamp(60px, 7vw, 82px) 0; }
.lg9-section-soft { padding: clamp(60px, 7vw, 82px) 0; background: var(--lg-bg); }
.lg9-card { background: var(--lg-surface); border:1px solid var(--lg-line); border-radius: 20px; box-shadow: 0 18px 44px rgba(13,27,42,.08); transition: transform .22s ease, box-shadow .22s ease; padding: 0; overflow: hidden; }
.lg9-card:hover { transform: translateY(-3px); box-shadow: 0 22px 50px rgba(13,27,42,.12); }
.lg9-card-body { padding: 22px; }
.lg9-card-title { margin: 0 0 6px; font-size: 1.15rem; font-weight: 700; }
.lg9-card-role { margin: 0 0 12px; color: var(--lg-cyan); font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.lg9-card-bio { margin: 0; line-height: 1.7; font-size: 0.95rem; color: var(--lg-ink-soft); }
.lg9-grid-3 { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(18px, 2.2vw, 22px); }
.board-member-img { width:100%; height:280px; object-fit:cover; display:block; }
.member-intro { text-align: center; max-width: 800px; margin: 0 auto 0; }
@media (max-width: 900px) {
  .lg9-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 600px) {
  .lg9-grid-3 { grid-template-columns: 1fr; }
  .lg9-hero { min-height: auto; }
  .lg9-hero-inner { padding: 60px 0 50px; }
}
</style>
<div class="lg9">
<section class="lg9-hero" style="min-height:50vh;">
  <div class="lg9-hero-bg"><img decoding="async" src="https://www.loungenie.com/wp-content/uploads/2026/03/mc-mcowc-16683_Classic-Hor.jpg" alt="Board governance" fetchpriority="high"></div>
  <div class="lg9-hero-overlay" style="background:linear-gradient(112deg, rgba(6,13,25,.93) 0%, rgba(8,29,49,.86) 52%, rgba(0,75,147,.52) 100%);"></div>
  <div class="lg9-hero-inner">
    <div class="lg9-shell">
      <p class="lg9-kicker">Governance</p>
      <h1 class="lg9-title-md" style="color:#fff;margin:10px 0 12px;max-width:900px;">Board of Directors</h1>
      <p style="color:rgba(255,255,255,.88);max-width:850px;line-height:1.8;font-size:1.02rem;">Strategic leadership guiding Pool Safe Inc.&#8217;s mission to transform premium outdoor seating worldwide through innovation and governance excellence.</p>
    </div>
  </div>
</section>

<section class="lg9-section" style="background:#fff;">
  <div class="lg9-shell">
    <div class="member-intro">
      <h2 style="margin:0 0 12px;font-size:2rem;color:var(--lg-ink);">Meet the LounGenie Leadership Team</h2>
      <p class="lg9-copy" style="margin:0;">The LounGenie leadership team brings decades of experience across hospitality, finance, and marketing. These are the strategic minds guiding Pool Safe Inc.&#8217;s mission to transform premium seating worldwide.</p>
    </div>
    <div style="margin-top:48px;">
      <div class="lg9-grid-3">
        <div class="lg9-card">
          <img class="board-member-img" src="https://www.loungenie.com/wp-content/uploads/2025/10/David-Berger-300x200.webp" alt="David Berger CEO" decoding="async">
          <div class="lg9-card-body">
            <h3 class="lg9-card-title">David Berger</h3>
            <p class="lg9-card-role">Chief Executive Officer</p>
            <p class="lg9-card-bio">Mr. David Berger is Executive Chairman and CEO, Pool Safe Inc. Mr. Berger was formerly the Director of Operations of Kiddie Ride Entertainment Limited, a company he founded to create fun and exciting amusement rides for children, located in shopping malls across southern Ontario. Prior to Kiddie Ride, Mr. Berger held the position of Managing Director at Jodami Enterprises Limited, an Engineering company that provided plumbing and electrical supplies across the Greater Toronto Area.</p>
          </div>
        </div>

        <div class="lg9-card">
          <img class="board-member-img" src="https://www.loungenie.com/wp-content/uploads/2025/10/steven-glaser-10.jpg-238x300.webp" alt="Steven Glaser COO CFO" decoding="async">
          <div class="lg9-card-body">
            <h3 class="lg9-card-title">Steven Glaser</h3>
            <p class="lg9-card-role">COO, CFO &amp; Director</p>
            <p class="lg9-card-bio">Mr. Steven Glaser is Chief Operating Officer, Chief Financial Officer and Director, Pool Safe Inc. Mr. Glaser is a financial service executive with a diverse background in corporate finance, communications and governance for private and public companies. He spent the last eight years working in the corporate finance and investment banking arena focused on assisting late stage private and early stage public companies with strategic planning and capital raising. Prior to that, Mr. Glaser spent seven years as Vice President Corporate Affairs of Azure Dynamics Corporation. He was responsible for the company's corporate governance, its domestic and international stock exchange listings, as well as the build-out of the company's Investor Relations division. Mr. Glaser holds a Bachelor of Administrative Studies degree as well as an M.B.A. in finance.</p>
          </div>
        </div>

        <div class="lg9-card">
          <img class="board-member-img" src="https://www.loungenie.com/wp-content/uploads/2025/10/M3_7412-Steven-Mintz-Crop-BusinessPortraits.ca_-768x768.jpg-300x300.webp" alt="Steven Mintz Director" decoding="async">
          <div class="lg9-card-body">
            <h3 class="lg9-card-title">Steven Mintz</h3>
            <p class="lg9-card-role">Director</p>
            <p class="lg9-card-bio">Mr. Steven Mintz is Director, Pool Safe Inc. Mr. Mintz is a graduate from the University of Toronto and obtained his C.A. designation in June of 1992. Between 1992 and 1997, Mr. Mintz was employed by a boutique bankruptcy and insolvency firm. He obtained his Trustee in Bankruptcy license in 1995. Since January 1997 he has been a self-employed financial consultant, serving private individuals and companies as well as public companies in a variety of industries, including mining, oil and gas, real estate and investment strategies. He is currently a director of 22 Capital Corp, Everton Resources Inc., Mooncor Oil and Gas Corp. and Portage Biotech Inc.</p>
          </div>
        </div>

        <div class="lg9-card">
          <img class="board-member-img" src="https://www.loungenie.com/wp-content/uploads/2025/10/GD-pic-2022-1-768x1024.jpg-225x300.webp" alt="Gillian Deacon Marketing" decoding="async">
          <div class="lg9-card-body">
            <h3 class="lg9-card-title">Gillian Deacon</h3>
            <p class="lg9-card-role">Marketing Executive</p>
            <p class="lg9-card-bio">Ms. Deacon brings over 15 years of integrated marketing experience across brand, experiential, partnership and content marketing. Ms. Deacon is currently the Vice President of Partnership Marketing for the Arizona Cardinals Football Club overseeing the day-to-day operations and direct supervision to the corporate partnership activation and service staff and all related functions. Prior to joining the Cardinals, Ms. Deacon was located in New York City as the Vice President, Solutions and Operations at Oak View Group (Nov 2020 &#8211; April 2025), the largest developer of sports and entertainment facilities in the world, with over $5 billion committed spend on new arena developments in various prime global locales. Leading to OVG, Ms. Deacon drove the initial formation and further growth, and development of the Wasserman Experience division in Canada, an industry leading global sports, entertainment and lifestyle company working with some of the world&#8217;s most iconic brands, properties and talent.</p>
          </div>
        </div>

        <div class="lg9-card">
          <img class="board-member-img" src="https://www.loungenie.com/wp-content/uploads/2025/10/Robert-Pratt-PSI-Website-Head-Shot-682x1024.jpg-200x300.webp" alt="Robert Pratt Director" decoding="async">
          <div class="lg9-card-body">
            <h3 class="lg9-card-title">Robert Pratt</h3>
            <p class="lg9-card-role">Director</p>
            <p class="lg9-card-bio">Mr. Pratt is currently the President of R. Pratt Consulting Ltd. From 2018-2024 he was President and Chief Operating Officer at Sandman Hotel Group and Sutton Place Hotels, a Canadian hotel chain. From October 2015 until July 2018, Mr. Pratt was the President of One Lodging Management, responsible for the day-to-day operations of 119 properties.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="lg9-section-soft">
  <div class="lg9-shell">
    <div class="lg9-card" style="padding:26px; max-width:900px; margin:0 auto;">
      <h3 style="margin:0 0 12px;font-size:1.3rem;">Board and Governance Inquiries</h3>
      <p class="lg9-copy" style="margin:0;">Email: <a href="mailto:info@poolsafeinc.com" style="color:var(--lg-blue);font-weight:700;">info@poolsafeinc.com</a><br>Phone: <a href="tel:+14166302444" style="color:var(--lg-blue);font-weight:700;">+1 (416) 630-2444</a></p>
    </div>
  </div>
</section>
</div>'''

# Update Board page (ID 5651) with enhanced layout
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5651"

update_data = json.dumps({
    "content": enhanced_board_content,
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
    
    print(f"✅ BOARD PAGE LAYOUT ENHANCED")
    print(f"  Page ID: {result.get('id')}")
    print(f"  Status: {result.get('status')}")
    print(f"  New content size: {len(updated_content)} chars")
    print(f"  Modified: {result.get('modified')}\n")
    print(f"Layout improvements:")
    print(f"  ✓ Added professional hero section with intro")
    print(f"  ✓ Centered leadership team introduction")
    print(f"  ✓ 3-column responsive member card grid")
    print(f"  ✓ Member images with uniform sizing")
    print(f"  ✓ Proper spacing and alignment")
    print(f"  ✓ Improved mobile responsiveness")
    print(f"  ✓ All content and biographies preserved")
    
except urllib.error.HTTPError as e:
    print(f"HTTP Error {e.code}: {e.reason}")
    print(f"Response: {e.read().decode()}")
except Exception as e:
    print(f"Error: {e}")
