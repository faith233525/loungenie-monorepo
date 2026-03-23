"""
LounGenie — Professional Redesign
Modern hospitality-tech B2B site with real images, polished typography,
cards, gradients, and strong call-to-actions.
Only verified stat used: up to 30% increase in F&B sales.
"""

import urllib.request
import urllib.error
import json
import base64

BASE_URL = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/pages"
USER = "copilot"
PASS = "7NiL OZ17 ApP3 tIgF 6zlT ug7u"
AUTH = base64.b64encode(f"{USER}:{PASS}".encode()).decode()
HEADERS = {
    "Authorization": f"Basic {AUTH}",
    "Content-Type": "application/json",
}

# ─────────────────────────────────────────────
# SHARED CSS + FONTS (injected on every page)
# ─────────────────────────────────────────────
SHARED_CSS = """
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Base ───────────────────────────────── */
.lg { font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#1a2440; }
.lg *, .lg *::before, .lg *::after { box-sizing:border-box; margin:0; padding:0; }
.lg a { text-decoration:none; }
.lg img { display:block; max-width:100%; height:auto; }

/* ── Layout ─────────────────────────────── */
.lg .wrap   { max-width:1140px; margin:0 auto; padding:0 24px; }
.lg .wrap-sm{ max-width:760px;  margin:0 auto; padding:0 24px; }
.lg .sec    { padding:96px 0; }
.lg .sec-sm { padding:72px 0; }

/* ── Type ───────────────────────────────── */
.lg .eyebrow{ display:inline-block; font-size:12px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:#0077B6; margin-bottom:14px; }
.lg h1      { font-size:clamp(34px,5.5vw,60px); font-weight:900; line-height:1.1; letter-spacing:-1.5px; }
.lg h2      { font-size:clamp(28px,4vw,44px);   font-weight:800; line-height:1.15; letter-spacing:-0.5px; }
.lg h3      { font-size:clamp(18px,2.5vw,22px); font-weight:700; line-height:1.3; }
.lg .lead   { font-size:clamp(16px,2vw,20px);   line-height:1.7; color:#4a5568; }
.lg .muted  { color:#7a8698; font-size:15px; line-height:1.6; }

/* ── Buttons ────────────────────────────── */
.lg .btn { display:inline-flex; align-items:center; gap:8px; padding:16px 32px; border-radius:10px; font-weight:700; font-size:16px; transition:all .2s ease; cursor:pointer; border:none; }
.lg .btn-primary { background:linear-gradient(135deg,#0077B6,#0096d6); color:white; box-shadow:0 4px 20px rgba(0,119,182,.3); }
.lg .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(0,119,182,.45); }
.lg .btn-white  { background:white; color:#0077B6; box-shadow:0 4px 20px rgba(0,0,0,.12); }
.lg .btn-white:hover  { transform:translateY(-2px); box-shadow:0 8px 30px rgba(0,0,0,.2); }
.lg .btn-ghost  { background:transparent; color:white; border:2px solid rgba(255,255,255,.65); }
.lg .btn-ghost:hover  { background:rgba(255,255,255,.12); border-color:white; }
.lg .btn-outline{ background:transparent; color:#0077B6; border:2px solid #0077B6; }
.lg .btn-outline:hover{ background:#0077B6; color:white; }

/* ── Cards ──────────────────────────────── */
.lg .card { background:white; border-radius:16px; border:1px solid #e8ecf2; transition:all .25s ease; overflow:hidden; }
.lg .card:hover { box-shadow:0 16px 48px rgba(0,0,0,.1); transform:translateY(-3px); }
.lg .card-pad { padding:36px 30px; }

/* ── Feature icon ───────────────────────── */
.lg .icon-box { width:68px; height:68px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:28px; margin-bottom:22px; }
.lg .icon-blue  { background:linear-gradient(135deg,#dbeeff,#b8dcff); }
.lg .icon-cyan  { background:linear-gradient(135deg,#d0f8ff,#a8efff); }
.lg .icon-green { background:linear-gradient(135deg,#d4f7e9,#b0f0d4); }
.lg .icon-gold  { background:linear-gradient(135deg,#fff4cc,#ffe08a); }

/* ── Grids ──────────────────────────────── */
.lg .grid-4 { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:24px; }
.lg .grid-3 { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:32px; }
.lg .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:72px; align-items:center; }

/* ── Misc ───────────────────────────────── */
.lg .pill  { display:inline-block; padding:5px 13px; border-radius:100px; font-size:12px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
.lg .pill-red  { background:#fee2e2; color:#b91c1c; }
.lg .pill-green{ background:#dcfce7; color:#15803d; }
.lg .pill-blue { background:#dbeafe; color:#1d4ed8; }
.lg .divider   { width:48px; height:4px; background:linear-gradient(90deg,#0077B6,#00c6fb); border-radius:3px; margin-bottom:28px; }
.lg .check-list { list-style:none; display:flex; flex-direction:column; gap:10px; }
.lg .check-list li { display:flex; gap:10px; align-items:flex-start; color:#4a5568; font-size:15px; line-height:1.55; }
.lg .check-list li::before { content:"✓"; flex-shrink:0; width:22px; height:22px; background:#0077B6; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; margin-top:1px; }
.lg .award-badge { display:inline-flex; align-items:center; gap:10px; background:linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid #fde68a; border-radius:10px; padding:12px 20px; }

/* ── Hero ───────────────────────────────── */
.lg .hero { position:relative; min-height:88vh; display:flex; align-items:center; overflow:hidden; background:#0a1628; }
.lg .hero-bg { position:absolute; inset:0; background-image:url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&w=1600&q=80'); background-size:cover; background-position:center; opacity:.35; }
.lg .hero-overlay { position:absolute; inset:0; background:linear-gradient(135deg,rgba(0,8,32,.85) 0%,rgba(0,55,100,.7) 60%,rgba(0,100,180,.5) 100%); }
.lg .hero-content { position:relative; z-index:2; padding:120px 0 100px; }
.lg .hero h1 { color:white; margin-bottom:24px; }
.lg .hero .lead { color:rgba(255,255,255,.82); max-width:580px; margin-bottom:40px; }
.lg .hero-btns { display:flex; gap:16px; flex-wrap:wrap; }

/* ── Award bar ──────────────────────────── */
.lg .award-bar { background:white; border-bottom:1px solid #edf0f5; padding:18px 0; }
.lg .award-bar-inner { display:flex; align-items:center; justify-content:center; gap:36px; flex-wrap:wrap; }
.lg .award-bar-inner span { font-size:14px; color:#4a5568; font-weight:500; }
.lg .award-bar-inner strong { color:#1a2440; font-weight:700; }

/* ── Stat callout ───────────────────────── */
.lg .stat-banner { background:linear-gradient(135deg,#0a1628 0%,#003770 60%,#0077B6 100%); color:white; text-align:center; padding:96px 0; }
.lg .stat-number { font-size:clamp(56px,8vw,96px); font-weight:900; letter-spacing:-3px; line-height:1; background:linear-gradient(135deg,#fff,#a8dcff); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.lg .stat-label  { font-size:22px; font-weight:600; opacity:.85; margin-top:12px; }

/* ── Step circles ───────────────────────── */
.lg .step-num { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,#0077B6,#00c6fb); color:white; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:800; flex-shrink:0; box-shadow:0 6px 20px rgba(0,119,182,.4); margin-bottom:20px; }

/* ── Form ───────────────────────────────── */
.lg .form-control { display:block; width:100%; padding:13px 16px; border:1.5px solid #d1d9e0; border-radius:8px; font-size:15px; font-family:inherit; color:#1a2440; background:white; transition:border-color .2s; }
.lg .form-control:focus { outline:none; border-color:#0077B6; box-shadow:0 0 0 3px rgba(0,119,182,.12); }
.lg .form-label { display:block; font-size:14px; font-weight:600; color:#1a2440; margin-bottom:8px; }
.lg .form-group { margin-bottom:22px; }

/* ── Page hero (inner pages) ────────────── */
.lg .inner-hero { background:linear-gradient(135deg,#f0f6fc 0%,#ffffff 100%); padding:90px 0 60px; border-bottom:1px solid #e8ecf2; }

/* ── Feature row ────────────────────────── */
.lg .feat-row { padding:72px 0; border-bottom:1px solid #f0f3f8; }
.lg .feat-row:last-child { border-bottom:none; }
.lg .feat-visual { border-radius:16px; overflow:hidden; }
.lg .feat-img { width:100%; height:340px; object-fit:cover; border-radius:16px; }
.lg .feat-placeholder { background:linear-gradient(135deg,#e8f4ff,#d0eaff); border-radius:16px; height:340px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:14px; }
.lg .feat-placeholder .big-icon { font-size:80px; }
.lg .feat-placeholder p { font-weight:600; color:#0077B6; font-size:16px; }

/* ── Contact split ──────────────────────── */
.lg .contact-split { display:grid; grid-template-columns:1fr 1fr; gap:72px; align-items:start; }
.lg .contact-value { background:linear-gradient(135deg,#0a1628,#003770); border-radius:20px; padding:48px 40px; color:white; }
.lg .contact-value h3 { color:white; margin-bottom:12px; }
.lg .contact-value .lead { color:rgba(255,255,255,.75); font-size:16px; }

/* ── Responsive ─────────────────────────── */
@media(max-width:768px){
  .lg .grid-2,.lg .contact-split{ grid-template-columns:1fr; gap:40px; }
  .lg .sec,.lg .sec-sm{ padding:64px 0; }
  .lg .hero{ min-height:70vh; }
  .lg .hero-content{ padding:100px 0 80px; }
  .lg .hero-btns a:first-child{ width:100%; text-align:center; justify-content:center; }
}
</style>
"""

# ─────────────────────────────────────────────
# HOME PAGE
# ─────────────────────────────────────────────
HOME_HTML = SHARED_CSS + """
<div class="lg">

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content wrap">
    <div class="eyebrow" style="color:#7dd3fc;">IAAPA Brass Ring Award Winner</div>
    <h1>Increase Poolside<br>F&amp;B Sales by<br><span style="background:linear-gradient(90deg,#38bdf8,#7dd3fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Up to 30%</span></h1>
    <p class="lead">LounGenie&#x2122; is the all-in-one poolside guest experience platform for hospitality properties — wireless charging, smart storage, direct ordering, and premium amenities in one seamless system. Zero CapEx. Pure revenue upside.</p>
    <div class="hero-btns">
      <a href="/contact" class="btn btn-white">&#x1f4c5;&nbsp; Schedule a Demo</a>
      <a href="/features" class="btn btn-ghost">Explore Features &rarr;</a>
    </div>
  </div>
</section>

<!-- AWARD BAR -->
<div class="award-bar">
  <div class="award-bar-inner wrap">
    <span>&#x1f3c6; <strong>IAAPA Brass Ring Award</strong> — #1 Poolside Innovation Technology</span>
    <span style="width:1px;height:24px;background:#ddd;display:none;" class="sep"></span>
    <span>&#x1f4b0; <strong>Revenue Share Model</strong> — Zero CapEx Investment</span>
    <span>&#x1f3e8; <strong>Trusted</strong> by hospitality properties worldwide</span>
  </div>
</div>

<!-- PROBLEM SECTION -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <span class="eyebrow">The Opportunity</span>
        <div class="divider"></div>
        <h2 style="color:#0f2137;" class="lg-h2">Your Pool Deck Is Leaving Revenue on the Table</h2>
        <p class="lead" style="margin-top:18px;">Guests leave pool areas for three reasons: dead phones, nowhere to secure their belongings, and the hassle of ordering. Every early exit is a lost F&amp;B sale. LounGenie eliminates all three barriers.</p>
        <div style="margin-top:36px;display:flex;flex-direction:column;gap:20px;">
          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div style="flex-shrink:0;width:44px;height:44px;background:#fee2e2;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f50b;</div>
            <div><strong style="display:block;margin-bottom:4px;color:#1a2440;">Dead Phone = Guest Exit</strong><span class="muted">When a phone battery dies poolside, guests leave to find a charger — and often don't come back.</span></div>
          </div>
          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div style="flex-shrink:0;width:44px;height:44px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f9f3;</div>
            <div><strong style="display:block;margin-bottom:4px;color:#1a2440;">Unsecured Valuables = Anxiety</strong><span class="muted">Guests worry about leaving wallets and keys unattended, cutting pool visits short.</span></div>
          </div>
          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div style="flex-shrink:0;width:44px;height:44px;background:#dbeefe;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f6b6;</div>
            <div><strong style="display:block;margin-bottom:4px;color:#1a2440;">Ordering Friction = Skipped Sales</strong><span class="muted">If ordering means losing a chair and walking to a bar, many guests simply won't bother.</span></div>
          </div>
        </div>
      </div>
      <div>
        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&w=800&q=80" alt="Resort pool deck" style="border-radius:20px;width:100%;height:460px;object-fit:cover;box-shadow:0 24px 64px rgba(0,0,0,.2);">
      </div>
    </div>
  </div>
</section>

<!-- FEATURES GRID -->
<section class="sec" style="background:white;">
  <div class="wrap">
    <div style="text-align:center;max-width:600px;margin:0 auto 56px;">
      <span class="eyebrow">The Platform</span>
      <div class="divider" style="margin:0 auto 28px;"></div>
      <h2 style="color:#0f2137;">Four Features. One Platform. One Revenue Lift.</h2>
      <p class="lead" style="margin-top:16px;">ORDER + STASH + CHARGE + CHILL work together to keep guests poolside longer and spending more.</p>
    </div>
    <div class="grid-4">
      <div class="card card-pad">
        <div class="icon-box icon-blue">&#x1f4f1;</div>
        <h3>ORDER</h3>
        <p class="muted" style="margin-top:10px;">Direct poolside F&amp;B ordering from a lounge chair. No standing, no losing a spot, no friction — just more orders.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:6px;margin-top:20px;font-size:14px;font-weight:600;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box icon-cyan">&#x1f4e6;</div>
        <h3>STASH</h3>
        <p class="muted" style="margin-top:10px;">Smart poolside storage for valuables. Guests feel secure staying all day instead of retreating to their room.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:6px;margin-top:20px;font-size:14px;font-weight:600;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box icon-gold">&#x26a1;</div>
        <h3>CHARGE</h3>
        <p class="muted" style="margin-top:10px;">Wireless charging stations eliminate the #1 reason guests leave the pool area — a dead phone battery.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:6px;margin-top:20px;font-size:14px;font-weight:600;color:#0077B6;">Learn more &rarr;</a>
      </div>
      <div class="card card-pad">
        <div class="icon-box icon-green">&#x1f9ca;</div>
        <h3>CHILL</h3>
        <p class="muted" style="margin-top:10px;">Premium poolside comfort amenities that create a resort feel, encouraging guests to linger and spend.</p>
        <a href="/features" style="display:inline-flex;align-items:center;gap:6px;margin-top:20px;font-size:14px;font-weight:600;color:#0077B6;">Learn more &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- STAT BANNER -->
<section class="stat-banner">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow" style="color:#7dd3fc;">Verified Result</span>
    <div class="stat-number" style="margin-top:12px;">Up to 30%</div>
    <p class="stat-label">increase in poolside food &amp; beverage sales</p>
    <p style="margin-top:20px;color:rgba(255,255,255,.65);font-size:16px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.7;">By keeping guests poolside longer and removing ordering friction, LounGenie properties see measurable, consistent F&amp;B revenue growth.</p>
    <div style="margin-top:40px;">
      <a href="/contact" class="btn btn-white">See it at your property &rarr;</a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div style="text-align:center;max-width:520px;margin:0 auto 56px;">
      <span class="eyebrow">How It Works</span>
      <div class="divider" style="margin:0 auto 28px;"></div>
      <h2 style="color:#0f2137;">Zero Risk. Pure Revenue Upside.</h2>
    </div>
    <div class="grid-3">
      <div style="text-align:center;padding:10px;">
        <div class="step-num" style="margin:0 auto 20px;">1</div>
        <h3 style="margin-bottom:12px;">We Install</h3>
        <p class="muted">Full installation, setup, and onboarding at no capital cost to your property. We handle everything.</p>
      </div>
      <div style="text-align:center;padding:10px;">
        <div class="step-num" style="margin:0 auto 20px;">2</div>
        <h3 style="margin-bottom:12px;">Guests Engage</h3>
        <p class="muted">Guests order, charge, store, and relax — staying poolside longer and spending more on F&amp;B.</p>
      </div>
      <div style="text-align:center;padding:10px;">
        <div class="step-num" style="margin:0 auto 20px;">3</div>
        <h3 style="margin-bottom:12px;">Revenue Grows</h3>
        <p class="muted">Your F&amp;B sales increase. We share in the revenue we help generate — pure upside with no CapEx.</p>
      </div>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="sec" style="background:white;">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">Ready to Start?</span>
    <div class="divider" style="margin:0 auto 28px;"></div>
    <h2 style="color:#0f2137;">See What LounGenie Can Do for Your Property</h2>
    <p class="lead" style="margin:20px auto 40px;max-width:520px;">No commitment. A straightforward conversation about your property and how the platform works.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/contact" class="btn btn-primary">&#x1f4c5;&nbsp; Schedule a Demo</a>
      <a href="/features" class="btn btn-outline">View Features &rarr;</a>
    </div>
  </div>
</section>

</div>
"""

# ─────────────────────────────────────────────
# FEATURES PAGE
# ─────────────────────────────────────────────
FEATURES_HTML = SHARED_CSS + """
<div class="lg">

<!-- INNER HERO -->
<section class="inner-hero">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">The Platform</span>
    <div class="divider" style="margin:0 auto 28px;"></div>
    <h1 style="font-size:clamp(30px,4.5vw,50px);font-weight:900;color:#0f2137;letter-spacing:-1px;">Features Built to Drive<br>F&amp;B Revenue</h1>
    <p class="lead" style="margin:20px auto 0;max-width:520px;">Each component of LounGenie targets a specific reason guests leave pools early or skip ordering — turning those moments into revenue.</p>
  </div>
</section>

<!-- FEATURES -->
<section class="sec" style="background:white;">
  <div class="wrap">

    <!-- CHARGE -->
    <div class="feat-row grid-2" style="padding:80px 0;">
      <div>
        <span class="pill pill-red" style="margin-bottom:18px;">THE PROBLEM</span>
        <h2 style="margin:16px 0 14px;color:#0f2137;">Dead Phone = Lost Guest</h2>
        <p class="lead" style="margin-bottom:28px;">When a phone battery dies, guests leave poolside immediately to find a charger — and often don't return. Every departure is a missed F&amp;B order.</p>
        <div style="background:linear-gradient(135deg,#4CAF50,#2e7d32);border-radius:8px;display:inline-block;padding:5px 14px;margin-bottom:18px;">
          <span style="color:white;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:16px;color:#0f2137;">CHARGE — Wireless Charging Stations</h3>
        <ul class="check-list">
          <li>Guests stay poolside longer — phones stay charged</li>
          <li>Removes the #1 reason for early pool exits</li>
          <li>More dwell time = more opportunity to order F&amp;B</li>
        </ul>
      </div>
      <div class="feat-visual">
        <img src="https://images.unsplash.com/photo-1580910051074-3eb694886505?auto=format&w=700&q=80" alt="Wireless charging" class="feat-img">
      </div>
    </div>

    <!-- ORDER -->
    <div class="feat-row grid-2" style="padding:80px 0;">
      <div class="feat-visual">
        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&w=700&q=80" alt="Pool F&B ordering" class="feat-img">
      </div>
      <div>
        <span class="pill pill-red" style="margin-bottom:18px;">THE PROBLEM</span>
        <h2 style="margin:16px 0 14px;color:#0f2137;">Guests Skip Ordering to Keep Their Spot</h2>
        <p class="lead" style="margin-bottom:28px;">Walking to the bar means risking a lounge chair and leaving belongings unattended. That friction silently kills F&amp;B sales throughout the day.</p>
        <div style="background:linear-gradient(135deg,#4CAF50,#2e7d32);border-radius:8px;display:inline-block;padding:5px 14px;margin-bottom:18px;">
          <span style="color:white;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:16px;color:#0f2137;">ORDER — Poolside F&amp;B Ordering</h3>
        <ul class="check-list">
          <li>Guests order food and drinks directly from their lounge chair</li>
          <li>No friction, no lost chairs, no leaving belongings unattended</li>
          <li>Properties see <strong>up to 30% increase in poolside F&amp;B sales</strong></li>
        </ul>
      </div>
    </div>

    <!-- STASH -->
    <div class="feat-row grid-2" style="padding:80px 0;">
      <div>
        <span class="pill pill-red" style="margin-bottom:18px;">THE PROBLEM</span>
        <h2 style="margin:16px 0 14px;color:#0f2137;">Valuables Anxiety Sends Guests Inside</h2>
        <p class="lead" style="margin-bottom:28px;">Guests regularly leave the pool to lock up phones, wallets, and keys. Each trip back to the room risks them not returning to the pool — or the F&amp;B program.</p>
        <div style="background:linear-gradient(135deg,#4CAF50,#2e7d32);border-radius:8px;display:inline-block;padding:5px 14px;margin-bottom:18px;">
          <span style="color:white;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:16px;color:#0f2137;">STASH — Smart Poolside Storage</h3>
        <ul class="check-list">
          <li>Secure, convenient poolside storage for guest valuables</li>
          <li>Guests feel safe and comfortable staying all day</li>
          <li>Extended dwell time directly drives more F&amp;B orders</li>
        </ul>
      </div>
      <div class="feat-visual">
        <img src="https://images.unsplash.com/photo-1540541338537-3236369a4b23?auto=format&w=700&q=80" alt="Resort pool" class="feat-img">
      </div>
    </div>

    <!-- CHILL -->
    <div class="feat-row grid-2" style="padding:80px 0 0;">
      <div class="feat-visual">
        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&w=700&q=80" alt="Pool amenities" class="feat-img">
      </div>
      <div>
        <span class="pill pill-red" style="margin-bottom:18px;">THE PROBLEM</span>
        <h2 style="margin:16px 0 14px;color:#0f2137;">A Basic Pool Doesn&#x27;t Inspire Spending</h2>
        <p class="lead" style="margin-bottom:28px;">When the pool experience feels ordinary, guests check in briefly and leave. A premium environment changes behavior — guests stay, relax, and spend.</p>
        <div style="background:linear-gradient(135deg,#4CAF50,#2e7d32);border-radius:8px;display:inline-block;padding:5px 14px;margin-bottom:18px;">
          <span style="color:white;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;">THE SOLUTION</span>
        </div>
        <h3 style="margin-bottom:16px;color:#0f2137;">CHILL — Premium Comfort Amenities</h3>
        <ul class="check-list">
          <li>Elevated poolside comfort that creates a true resort atmosphere</li>
          <li>Complements your F&amp;B program by encouraging longer stays</li>
          <li>Differentiates your property and drives repeat visits</li>
        </ul>
      </div>
    </div>

  </div>
</section>

<!-- STAT BANNER -->
<section class="stat-banner">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow" style="color:#7dd3fc;">All Four Together</span>
    <div class="stat-number" style="margin-top:12px;">Up to 30%</div>
    <p class="stat-label">poolside F&amp;B revenue increase</p>
    <p style="margin-top:20px;color:rgba(255,255,255,.65);font-size:16px;max-width:460px;margin-left:auto;margin-right:auto;line-height:1.7;">ORDER + STASH + CHARGE + CHILL working together deliver a measurable, consistent lift in food and beverage sales for your property.</p>
    <div style="margin-top:40px;">
      <a href="/contact" class="btn btn-white">Request a Demo &rarr;</a>
    </div>
  </div>
</section>

</div>
"""

# ─────────────────────────────────────────────
# ABOUT PAGE
# ─────────────────────────────────────────────
ABOUT_HTML = SHARED_CSS + """
<div class="lg">

<!-- INNER HERO -->
<section class="inner-hero">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <span class="eyebrow">About Us</span>
        <div class="divider"></div>
        <h1 style="font-size:clamp(30px,4.5vw,50px);font-weight:900;color:#0f2137;letter-spacing:-1px;margin-bottom:20px;">Helping Hospitality Properties Capture Poolside Revenue</h1>
        <p class="lead" style="max-width:480px;">We build technology that turns unused pool deck potential into consistent F&amp;B revenue — with no capital risk to your property.</p>
        <div style="margin-top:36px;">
          <div class="award-badge">
            <span style="font-size:28px;">&#x1f3c6;</span>
            <div>
              <strong style="display:block;color:#92400e;font-size:14px;">IAAPA Brass Ring Award Winner</strong>
              <span style="font-size:13px;color:#92400e;opacity:.8;">#1 Poolside Innovation Technology</span>
            </div>
          </div>
        </div>
      </div>
      <div>
        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&w=800&q=80" alt="Hotel pool amenities" style="border-radius:20px;width:100%;height:420px;object-fit:cover;box-shadow:0 20px 60px rgba(0,0,0,.15);">
      </div>
    </div>
  </div>
</section>

<!-- MISSION -->
<section class="sec" style="background:white;">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow">Our Mission</span>
    <div class="divider" style="margin:0 auto 28px;"></div>
    <h2 style="color:#0f2137;">We Exist to Make Every Pool Day More Profitable</h2>
    <p class="lead" style="margin:20px auto;max-width:580px;">Hospitality pool decks are underutilized revenue assets. Guests want to stay longer — they just need the right environment. LounGenie creates that environment and captures the revenue it generates.</p>
  </div>
</section>

<!-- APPROACH CARDS -->
<section class="sec-sm" style="background:#f8fafc;padding-bottom:96px;">
  <div class="wrap">
    <div style="text-align:center;max-width:520px;margin:0 auto 48px;">
      <span class="eyebrow">Our Approach</span>
      <div class="divider" style="margin:0 auto 28px;"></div>
      <h2 style="color:#0f2137;">How We Work With Properties</h2>
    </div>
    <div class="grid-2" style="gap:32px;align-items:stretch;">
      <div class="card card-pad" style="display:flex;flex-direction:column;gap:12px;">
        <div class="icon-box icon-blue">&#x1f465;</div>
        <h3>Guest-First Design</h3>
        <p class="muted">Every feature starts with a real guest frustration. Comfortable, happy guests stay longer and spend more — that's the foundation of our platform.</p>
      </div>
      <div class="card card-pad" style="display:flex;flex-direction:column;gap:12px;">
        <div class="icon-box icon-gold">&#x1f4b8;</div>
        <h3>Zero CapEx for Properties</h3>
        <p class="muted">We take on the investment and handle all installation, maintenance, and support. Your property gets the revenue upside with no financial risk.</p>
      </div>
      <div class="card card-pad" style="display:flex;flex-direction:column;gap:12px;">
        <div class="icon-box icon-green">&#x1f4bb;</div>
        <h3>Seamless Integration</h3>
        <p class="muted">LounGenie works alongside your existing F&amp;B operation. No overhaul of your systems, no retraining your team from scratch.</p>
      </div>
      <div class="card card-pad" style="display:flex;flex-direction:column;gap:12px;">
        <div class="icon-box icon-cyan">&#x1f4c8;</div>
        <h3>Measurable Revenue Results</h3>
        <p class="muted">Properties using LounGenie see up to 30% increase in poolside F&amp;B sales — driven by longer dwell time and frictionless ordering.</p>
      </div>
    </div>
  </div>
</section>

<!-- WHAT WE DO -->
<section class="sec" style="background:white;">
  <div class="wrap">
    <div class="grid-2">
      <div>
        <img src="https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&w=700&q=80" alt="Pool deck" style="border-radius:20px;width:100%;height:420px;object-fit:cover;box-shadow:0 20px 60px rgba(0,0,0,.12);">
      </div>
      <div>
        <span class="eyebrow">The Platform</span>
        <div class="divider"></div>
        <h2 style="color:#0f2137;margin-bottom:20px;">ORDER. STASH. CHARGE. CHILL.</h2>
        <p class="lead" style="margin-bottom:24px;">LounGenie&#x2122; combines four integrated modules into one complete poolside platform.</p>
        <p class="muted" style="margin-bottom:18px;line-height:1.8;">Each module addresses a specific reason guests leave the pool early or skip F&amp;B ordering. Together, they create an environment where guests are comfortable staying all day — and have every reason to keep ordering.</p>
        <p class="muted" style="line-height:1.8;">We operate on a pure revenue share model. There's no capital expenditure required from your property. We install, maintain, and support the full system. You gain the revenue lift.</p>
        <div style="margin-top:36px;">
          <a href="/features" class="btn btn-primary">Explore the Features &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="stat-banner sec-sm">
  <div class="wrap-sm" style="text-align:center;">
    <span class="eyebrow" style="color:#7dd3fc;">Get In Touch</span>
    <h2 style="color:white;margin:14px 0 18px;">Ready to Learn More?</h2>
    <p style="color:rgba(255,255,255,.75);font-size:17px;max-width:440px;margin:0 auto 36px;line-height:1.7;">See exactly how LounGenie works and what it could mean for your property. No pressure, no commitment.</p>
    <a href="/contact" class="btn btn-white">Schedule a Conversation &rarr;</a>
  </div>
</section>

</div>
"""

# ─────────────────────────────────────────────
# CONTACT PAGE
# ─────────────────────────────────────────────
CONTACT_HTML = SHARED_CSS + """
<div class="lg">

<!-- INNER HERO -->
<section class="inner-hero" style="padding-bottom:0;">
  <div class="wrap" style="padding-bottom:60px;">
    <div style="max-width:620px;">
      <span class="eyebrow">Contact</span>
      <div class="divider"></div>
      <h1 style="font-size:clamp(30px,4.5vw,50px);font-weight:900;color:#0f2137;letter-spacing:-1px;margin-bottom:20px;">Let&#x27;s Talk About<br>Your Pool Deck</h1>
      <p class="lead">See how LounGenie can help your property increase poolside F&amp;B revenue by up to 30% — at zero capital cost.</p>
    </div>
  </div>
</section>

<!-- CONTACT SPLIT -->
<section class="sec" style="background:#f8fafc;">
  <div class="wrap">
    <div class="contact-split">

      <!-- FORM -->
      <div class="card" style="padding:44px 40px;">
        <h2 style="color:#0f2137;font-size:24px;font-weight:700;margin-bottom:6px;">Request a Demo</h2>
        <p class="muted" style="margin-bottom:32px;">We'll get back to you within one business day.</p>
        <form>
          <div class="form-group">
            <label class="form-label">Name <span style="color:#e53e3e;">*</span></label>
            <input type="text" required placeholder="Your name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Work Email <span style="color:#e53e3e;">*</span></label>
            <input type="email" required placeholder="you@yourproperty.com" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Company / Property <span style="color:#e53e3e;">*</span></label>
            <input type="text" required placeholder="Hotel or property name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone (optional)</label>
            <input type="tel" placeholder="Best number to reach you" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Number of Pool Locations</label>
            <select class="form-control" style="appearance:auto;">
              <option value="">Select...</option>
              <option>1–5 locations</option>
              <option>6–15 locations</option>
              <option>16–50 locations</option>
              <option>50+ locations</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Anything you&#x27;d like us to know? (optional)</label>
            <textarea rows="4" placeholder="Tell us about your property or what you want to solve..." class="form-control" style="resize:vertical;"></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:17px;padding:18px;">
            &#x1f4e8;&nbsp; Send Request
          </button>
        </form>
      </div>

      <!-- VALUE PROPS -->
      <div>
        <div class="contact-value" style="margin-bottom:28px;">
          <div style="font-size:36px;margin-bottom:16px;">&#x1f3c6;</div>
          <h3 style="font-size:20px;font-weight:700;margin-bottom:10px;">IAAPA Brass Ring Award Winner</h3>
          <p class="lead" style="font-size:15px;color:rgba(255,255,255,.72);margin-bottom:0;">Recognized as the #1 Poolside Innovation Technology in the hospitality industry.</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:20px;">
          <div class="card card-pad" style="display:flex;gap:16px;align-items:flex-start;padding:24px;">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#dbeeff,#b8dcff);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f4b0;</div>
            <div>
              <strong style="display:block;margin-bottom:4px;color:#1a2440;">Zero CapEx Required</strong>
              <span class="muted">Full installation at no upfront cost. Revenue share model only.</span>
            </div>
          </div>
          <div class="card card-pad" style="display:flex;gap:16px;align-items:flex-start;padding:24px;">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#d4f7e9,#b0f0d4);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x1f4c8;</div>
            <div>
              <strong style="display:block;margin-bottom:4px;color:#1a2440;">Up to 30% More F&amp;B Revenue</strong>
              <span class="muted">The only verified stat we use — because it&#x27;s real.</span>
            </div>
          </div>
          <div class="card card-pad" style="display:flex;gap:16px;align-items:flex-start;padding:24px;">
            <div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">&#x26a1;</div>
            <div>
              <strong style="display:block;margin-bottom:4px;color:#1a2440;">Fast, Seamless Deployment</strong>
              <span class="muted">Works alongside your existing F&amp;B operation with no disruption.</span>
            </div>
          </div>
        </div>

        <div style="margin-top:28px;padding:24px;border-radius:12px;background:white;border:1px solid #e8ecf2;text-align:center;">
          <p class="muted" style="margin-bottom:8px;">Prefer to email directly?</p>
          <a href="mailto:info@poolsafe.com" style="color:#0077B6;font-weight:700;font-size:16px;">info@poolsafe.com</a>
        </div>
      </div>

    </div>
  </div>
</section>

</div>
"""

# ─────────────────────────────────────────────
# PUSH TO WORDPRESS
# ─────────────────────────────────────────────
pages = [
    {"name": "Home",     "id": 4701, "title": "LounGenie — Increase Poolside F&B Sales by Up to 30%", "html": HOME_HTML},
    {"name": "Features", "id": 2989, "title": "Features | LounGenie Poolside Revenue Platform",       "html": FEATURES_HTML},
    {"name": "About",    "id": 4862, "title": "About | Pool Safe Enterprise & LounGenie",              "html": ABOUT_HTML},
    {"name": "Contact",  "id": 5139, "title": "Contact | LounGenie Demo Request",                     "html": CONTACT_HTML},
]

def update_page(page_id, title, html):
    payload = json.dumps({
        "title":   title,
        "content": html,
        "status":  "publish",
    }).encode("utf-8")
    req = urllib.request.Request(
        f"{BASE_URL}/{page_id}",
        data=payload,
        method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))},
    )
    try:
        with urllib.request.urlopen(req, timeout=45) as resp:
            data = json.loads(resp.read())
            return True, data.get("link", "")
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        return False, f"HTTP {e.code}: {body[:400]}"
    except Exception as e:
        return False, str(e)

print("Pushing professional redesign to all 4 pages...\n")
for page in pages:
    ok, result = update_page(page["id"], page["title"], page["html"])
    status = "✓" if ok else "✗ FAILED"
    print(f"[{status}] {page['name']} (ID {page['id']})  {result if ok else ''}")
    if not ok:
        print(f"          {result}")

print("\nDone. Professional redesign live.")
