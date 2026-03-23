import base64
import json
import urllib.error
import urllib.request

AUTH = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
BASE = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json", "User-Agent": "Mozilla/5.0"}
PAGES = BASE + "/pages"
ROOT = "https://loungenie.com/Loungenie%E2%84%A2"
UP = ROOT + "/wp-content/uploads/2026/03/"

IMG = {
    "hero": UP + "hero9-bg-1.jpg",
    "hil_main": UP + "Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg",
    "hil_order": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-2-scaled.jpg",
    "hil_daybed": UP + "Hilton-Waikoloa-Village-2018-10-Aloha-Falls-Cabana-3-scaled.jpg",
    "hil_aloha": UP + "Hilton-Wakoloa-Village-2018-10-Aloha-Falls-Cabana-1-scaled.jpg",
    "grove": UP + "The-Grove-7-scaled.jpg",
    "grove_soft": UP + "The-Grove-5.jpg",
    "sea": UP + "Sea-World-San-Diego.jpg",
    "contact": UP + "3-VOR-cabana-e1773774348955.jpg",
    "cowa1": UP + "IMG_3241-scaled-1.jpg",
    "cowa2": UP + "IMG_3239-scaled-1.jpg",
    "cowa3": UP + "IMG_3235-scaled-1.jpg",
    "cowa4": UP + "IMG_3233-scaled-1.jpg",
}

PAGE_DATA = {
    4701: {
        "title": "LounGenie | Smart Poolside Revenue Platform",
        "excerpt": "LounGenie helps hotels, resorts, and water parks grow poolside food and beverage revenue with QR ordering, secure storage, charging, and premium guest amenities.",
        "featured_media": 8382,
    },
    2989: {
        "title": "LounGenie Features | ORDER, STASH, CHARGE, CHILL",
        "excerpt": "Explore the LounGenie platform features that improve guest convenience and support stronger poolside food and beverage performance.",
        "featured_media": 8380,
    },
    4862: {
        "title": "About LounGenie | Hospitality Revenue Innovation",
        "excerpt": "Learn how LounGenie combines hospitality-focused product design with a zero-capital deployment model for hotels and resorts.",
        "featured_media": 8379,
    },
    5139: {
        "title": "Contact LounGenie | Schedule a Poolside Revenue Demo",
        "excerpt": "Contact LounGenie to discuss your property, request a demonstration, and explore how the platform can fit your poolside operation.",
        "featured_media": 9073,
    },
    5285: {
        "title": "LounGenie Videos | Product and Installation Demos",
        "excerpt": "Watch LounGenie videos featuring real installations, product walkthroughs, and resort poolside use cases.",
        "featured_media": 8382,
    },
    5223: {
        "title": "LounGenie Gallery | Cabana Installation Photos",
        "excerpt": "View installation photos showing how LounGenie fits hotels, resorts, and water parks across premium poolside environments.",
        "featured_media": 8378,
    },
}

GLOBAL_STYLE = """
<!-- wp:html -->
<style>
:root {
  --lgx-ink: #0d1b2a;
  --lgx-ink-soft: #243447;
  --lgx-blue: #0055a5;
  --lgx-cyan: #00a7e8;
  --lgx-line: #dce6f0;
  --lgx-bg: #f4f8fb;
}
.page-id-4701 .wp-block-post-title,
.page-id-2989 .wp-block-post-title,
.page-id-4862 .wp-block-post-title,
.page-id-5139 .wp-block-post-title,
.page-id-5285 .wp-block-post-title,
.page-id-5223 .wp-block-post-title,
.page-id-4701 .entry-title,
.page-id-2989 .entry-title,
.page-id-4862 .entry-title,
.page-id-5139 .entry-title,
.page-id-5285 .entry-title,
.page-id-5223 .entry-title { display: none !important; }
.lgx-shell { max-width: 1280px; margin: 0 auto; padding: 0 26px; }
.lgx-narrow { max-width: 880px; margin: 0 auto; padding: 0 26px; }
.lgx-kicker { text-transform: uppercase; letter-spacing: 2px; font-size: 11px; font-weight: 800; color: var(--lgx-cyan); }
.lgx-card { background: #fff; border: 1px solid var(--lgx-line); border-radius: 18px; box-shadow: 0 18px 44px rgba(13,27,42,.06); }
.lgx-stat { background: linear-gradient(135deg, #0d1b2a, #123559 60%, #0055a5); border-radius: 24px; color: white; }
.lgx-chip { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,.14); color:#fff; font-size:12px; font-weight:700; }
.lgx-btn-row { display:flex; gap:14px; flex-wrap:wrap; }
.lgx-muted { color:#607286; }
.lgx-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:22px; }
.lgx-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:26px; }
.lgx-media { border-radius: 20px; overflow: hidden; box-shadow: 0 24px 56px rgba(13,27,42,.16); }
.lgx-media img { width: 100%; height: 100%; object-fit: cover; display:block; }
@media (max-width: 900px) {
  .lgx-grid-3, .lgx-grid-2 { grid-template-columns:1fr; }
  .lgx-shell, .lgx-narrow { padding: 0 16px; }
}
</style>
<!-- /wp:html -->
"""


def home_content():
    return GLOBAL_STYLE + f"""
<!-- wp:html -->
<section style="position:relative;min-height:82vh;display:flex;align-items:center;overflow:hidden;background:#09131f;">
  <img src="{IMG['hero']}" alt="Modern resort cabana with LounGenie" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.38;">
  <div style="position:absolute;inset:0;background:linear-gradient(110deg,rgba(9,19,31,.92) 0%,rgba(14,37,60,.84) 48%,rgba(0,85,165,.52) 100%);"></div>
  <div class="lgx-shell" style="position:relative;z-index:2;padding-top:92px;padding-bottom:92px;display:grid;grid-template-columns:1.15fr .85fr;gap:36px;align-items:center;">
    <div>
      <div class="lgx-chip">IAAPA Brass Ring Award Winner</div>
      <p class="lgx-kicker" style="margin:24px 0 10px;">Smart Poolside Revenue Platform</p>
      <h1 style="color:#fff;font-size:clamp(2.5rem,6vw,5.3rem);line-height:1.02;letter-spacing:-2px;margin:0 0 18px;font-weight:900;max-width:860px;">A modern poolside experience that drives measurable revenue.</h1>
      <p style="color:rgba(255,255,255,.82);font-size:1.14rem;line-height:1.8;max-width:720px;margin:0 0 30px;">LounGenie combines QR ordering, secure storage, device charging, and premium guest convenience in one polished commercial unit built for hotels, resorts, and water parks.</p>
      <div class="lgx-btn-row">
        <a href="{ROOT}/index.php/contact-loungenie/" style="display:inline-flex;align-items:center;justify-content:center;padding:15px 24px;border-radius:12px;background:#fff;color:#0d1b2a;text-decoration:none;font-weight:800;box-shadow:0 12px 28px rgba(0,0,0,.18);">Schedule a Demo</a>
        <a href="{ROOT}/index.php/poolside-amenity-unit/" style="display:inline-flex;align-items:center;justify-content:center;padding:15px 24px;border-radius:12px;border:1px solid rgba(255,255,255,.3);color:#fff;text-decoration:none;font-weight:800;background:rgba(255,255,255,.06);">Explore Features</a>
      </div>
    </div>
    <div>
      <div class="lgx-card" style="padding:22px;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);">
        <div class="lgx-media" style="aspect-ratio:4/3;">
          <img src="{IMG['hil_main']}" alt="LounGenie unit installed at a Hilton cabana">
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px;">
          <div style="padding:14px 16px;border-radius:14px;background:#f4f8fb;border:1px solid #e3ebf4;"><strong style="display:block;color:#0d1b2a;font-size:13px;">Zero CapEx</strong><span class="lgx-muted" style="font-size:13px;line-height:1.6;">Deployment model designed to reduce adoption friction.</span></div>
          <div style="padding:14px 16px;border-radius:14px;background:#f4f8fb;border:1px solid #e3ebf4;"><strong style="display:block;color:#0d1b2a;font-size:13px;">Up to 30%</strong><span class="lgx-muted" style="font-size:13px;line-height:1.6;">Improvement in poolside F&amp;B revenue.</span></div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:76px 0;background:#f4f8fb;">
  <div class="lgx-shell">
    <div style="max-width:760px;margin:0 auto 34px;text-align:center;">
      <p class="lgx-kicker">Why Operators Care</p>
      <h2 style="font-size:clamp(2rem,4vw,3.2rem);margin:10px 0 14px;color:#0d1b2a;line-height:1.08;font-weight:850;">Built for a premium guest journey and stronger poolside performance.</h2>
      <p class="lgx-muted" style="font-size:1.04rem;line-height:1.8;">The platform removes common reasons guests leave the pool early and makes ordering easier in the moments that matter.</p>
    </div>
    <div class="lgx-grid-3">
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Order</p><h3 style="margin:0 0 10px;font-size:1.3rem;color:#0d1b2a;">Capture in-seat demand.</h3><p class="lgx-muted" style="line-height:1.8;">QR ordering reduces service friction and keeps guests in their premium seat while they browse, order, and continue spending.</p></div>
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Stay</p><h3 style="margin:0 0 10px;font-size:1.3rem;color:#0d1b2a;">Reduce early departures.</h3><p class="lgx-muted" style="line-height:1.8;">Secure storage and charging help guests stay poolside longer instead of returning to the room or leaving to find power.</p></div>
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Elevate</p><h3 style="margin:0 0 10px;font-size:1.3rem;color:#0d1b2a;">Strengthen premium perception.</h3><p class="lgx-muted" style="line-height:1.8;">The product itself looks intentional and upscale, supporting a more modern guest experience on the deck.</p></div>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:82px 0;background:#fff;">
  <div class="lgx-shell lgx-grid-2" style="align-items:center;">
    <div class="lgx-media" style="aspect-ratio:4/3;"><img src="{IMG['hil_order']}" alt="Poolside ordering and hospitality experience"></div>
    <div>
      <p class="lgx-kicker">Measured Outcome</p>
      <div class="lgx-stat" style="padding:34px 30px;margin-top:10px;">
        <div style="font-size:clamp(3rem,7vw,5rem);font-weight:900;line-height:1;margin-bottom:8px;">Up to 30%</div>
        <div style="font-size:1.15rem;font-weight:700;margin-bottom:10px;">Increase in poolside food and beverage revenue</div>
        <p style="margin:0;color:rgba(255,255,255,.8);line-height:1.8;">A stronger guest experience translates into more time on deck, fewer interruptions, and more opportunities to order.</p>
      </div>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:76px 0;background:#f4f8fb;">
  <div class="lgx-shell">
    <div style="display:flex;justify-content:space-between;gap:20px;align-items:end;flex-wrap:wrap;margin-bottom:24px;">
      <div><p class="lgx-kicker" style="margin:0 0 10px;">Real Installations</p><h2 style="margin:0;color:#0d1b2a;font-size:clamp(1.9rem,4vw,3rem);font-weight:850;">Modern environments where LounGenie fits naturally.</h2></div>
      <a href="{ROOT}/index.php/cabana-installation-photos/" style="text-decoration:none;color:#0055a5;font-weight:800;">View full gallery</a>
    </div>
    <div class="lgx-grid-3">
      <div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['hil_aloha']}" alt="Hilton Waikoloa installation"></div>
      <div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['grove']}" alt="The Grove Resort installation"></div>
      <div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['sea']}" alt="SeaWorld installation"></div>
    </div>
  </div>
</section>
<!-- /wp:html -->
"""


def features_content():
    return GLOBAL_STYLE + f"""
<!-- wp:html -->
<section style="padding:74px 0 54px;background:linear-gradient(180deg,#f4f8fb 0%,#ffffff 100%);">
  <div class="lgx-narrow" style="text-align:center;">
    <p class="lgx-kicker">Platform Features</p>
    <h1 style="font-size:clamp(2.2rem,5vw,4rem);line-height:1.04;margin:10px 0 14px;color:#0d1b2a;font-weight:900;">A smarter poolside system built for modern hospitality.</h1>
    <p class="lgx-muted" style="font-size:1.08rem;line-height:1.8;">ORDER, STASH, CHARGE, and CHILL work together to remove guest friction and support stronger commercial performance.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:0 0 78px;background:#fff;">
  <div class="lgx-shell" style="display:flex;flex-direction:column;gap:28px;">
    <div class="lgx-card" style="padding:24px;"><div class="lgx-grid-2" style="align-items:center;"><div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['hil_order']}" alt="Order from seat"></div><div><p class="lgx-kicker">Order</p><h2 style="margin:8px 0 12px;color:#0d1b2a;font-size:2rem;font-weight:850;">Faster poolside ordering with less service friction.</h2><p class="lgx-muted" style="line-height:1.85;">Guests can browse and order directly from their seat through a simple mobile flow, helping operators capture more demand at the moment it exists.</p><ul style="color:#455468;line-height:1.9;"><li>Reduces walk-away order loss</li><li>Supports better premium-seat retention</li><li>Improves peak-hour ordering convenience</li></ul></div></div></div>
    <div class="lgx-card" style="padding:24px;"><div class="lgx-grid-2" style="align-items:center;"><div><p class="lgx-kicker">Stash</p><h2 style="margin:8px 0 12px;color:#0d1b2a;font-size:2rem;font-weight:850;">Secure storage that helps guests stay put.</h2><p class="lgx-muted" style="line-height:1.85;">Valuables storage reduces the interruptions that pull guests away from the pool deck and shortens their stay.</p><ul style="color:#455468;line-height:1.9;"><li>Supports longer dwell time</li><li>Improves peace of mind for guests</li><li>Fits premium cabana environments naturally</li></ul></div><div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['hil_main']}" alt="Secure storage feature"></div></div></div>
    <div class="lgx-card" style="padding:24px;"><div class="lgx-grid-2" style="align-items:center;"><div class="lgx-media" style="aspect-ratio:16/11;"><img src="{IMG['grove_soft']}" alt="Charging and premium comfort"></div><div><p class="lgx-kicker">Charge + Chill</p><h2 style="margin:8px 0 12px;color:#0d1b2a;font-size:2rem;font-weight:850;">Convenience features that feel premium, not improvised.</h2><p class="lgx-muted" style="line-height:1.85;">Charging support and refined guest amenities help the experience feel modern and intentionally designed, not patched together.</p><ul style="color:#455468;line-height:1.9;"><li>Less battery-driven churn</li><li>Stronger premium guest perception</li><li>Better all-day engagement on deck</li></ul></div></div></div>
  </div>
</section>
<!-- /wp:html -->
"""


def about_content():
    return GLOBAL_STYLE + f"""
<!-- wp:html -->
<section style="padding:80px 0;background:linear-gradient(135deg,#0d1b2a,#11314f 55%,#0055a5);color:#fff;">
  <div class="lgx-shell lgx-grid-2" style="align-items:center;">
    <div>
      <p class="lgx-kicker" style="color:#8ad8ff;">About LounGenie</p>
      <h1 style="font-size:clamp(2.1rem,5vw,4rem);line-height:1.05;margin:10px 0 16px;font-weight:900;">A modern hospitality product built around guest behavior.</h1>
      <p style="color:rgba(255,255,255,.82);line-height:1.9;font-size:1.05rem;">LounGenie is designed to help operators create a better poolside experience while making revenue performance easier to improve and easier to understand.</p>
    </div>
    <div class="lgx-media" style="aspect-ratio:4/3;"><img src="{IMG['hil_daybed']}" alt="LounGenie in a daybed environment"></div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:78px 0;background:#fff;">
  <div class="lgx-shell">
    <div class="lgx-grid-3">
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Model</p><h3 style="margin:0 0 10px;font-size:1.28rem;color:#0d1b2a;">Zero-capital deployment</h3><p class="lgx-muted" style="line-height:1.8;">The rollout model is intended to make adoption practical for operators who want results without heavy upfront risk.</p></div>
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Fit</p><h3 style="margin:0 0 10px;font-size:1.28rem;color:#0d1b2a;">Works inside premium environments</h3><p class="lgx-muted" style="line-height:1.8;">The product is designed to look intentional in upscale poolside settings, not like a generic utility add-on.</p></div>
      <div class="lgx-card" style="padding:28px;"><p class="lgx-kicker" style="margin:0 0 10px;">Focus</p><h3 style="margin:0 0 10px;font-size:1.28rem;color:#0d1b2a;">Guest-first commercial thinking</h3><p class="lgx-muted" style="line-height:1.8;">The design philosophy starts with guest convenience because that is where better operational outcomes begin.</p></div>
    </div>
  </div>
</section>
<!-- /wp:html -->
"""


def contact_content():
    return GLOBAL_STYLE + f"""
<!-- wp:html -->
<section style="padding:76px 0;background:linear-gradient(180deg,#f4f8fb,#ffffff);">
  <div class="lgx-shell lgx-grid-2" style="align-items:start;">
    <div>
      <p class="lgx-kicker">Contact</p>
      <h1 style="font-size:clamp(2.1rem,4.8vw,3.8rem);line-height:1.05;margin:10px 0 16px;color:#0d1b2a;font-weight:900;">Let’s talk about your poolside environment.</h1>
      <p class="lgx-muted" style="line-height:1.9;font-size:1.05rem;">If you want to understand how LounGenie could fit your property, we can walk through your current setup, guest experience goals, and operational requirements.</p>
      <div class="lgx-media" style="aspect-ratio:16/11;margin-top:24px;"><img src="{IMG['contact']}" alt="Contact LounGenie"></div>
      <p style="margin-top:18px;color:#0d1b2a;font-weight:800;">info@poolsafeinc.com</p>
    </div>
    <div class="lgx-card" style="padding:28px;">
      <p class="lgx-kicker" style="margin:0 0 8px;">Request a Demo</p>
      <h2 style="margin:0 0 14px;color:#0d1b2a;font-size:1.8rem;font-weight:850;">Start the conversation.</h2>
      <p class="lgx-muted" style="line-height:1.8;margin-bottom:20px;">Share your details and we’ll follow up to discuss fit, rollout considerations, and next steps.</p>
      <form action="https://formsubmit.co/info@poolsafeinc.com" method="POST" style="display:grid;gap:14px;">
        <input type="hidden" name="_captcha" value="false">
        <input type="hidden" name="_subject" value="New Demo Request - LounGenie">
        <label style="font-size:13px;font-weight:700;color:#0d1b2a;">Name<input name="name" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d5e0eb;border-radius:10px"></label>
        <label style="font-size:13px;font-weight:700;color:#0d1b2a;">Email<input type="email" name="email" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d5e0eb;border-radius:10px"></label>
        <label style="font-size:13px;font-weight:700;color:#0d1b2a;">Property<input name="property" required style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d5e0eb;border-radius:10px"></label>
        <label style="font-size:13px;font-weight:700;color:#0d1b2a;">Message<textarea name="message" rows="5" style="width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d5e0eb;border-radius:10px"></textarea></label>
        <button type="submit" style="padding:14px 18px;border:0;border-radius:12px;background:#0055a5;color:#fff;font-weight:800;font-size:15px;cursor:pointer;">Send Request</button>
      </form>
    </div>
  </div>
</section>
<!-- /wp:html -->
"""


def videos_content():
    return GLOBAL_STYLE + """
<!-- wp:html -->
<section style="padding:76px 0 44px;background:linear-gradient(180deg,#0d1b2a,#102b46);color:#fff;">
  <div class="lgx-narrow" style="text-align:center;">
    <p class="lgx-kicker" style="color:#8ad8ff;">Videos</p>
    <h1 style="font-size:clamp(2.1rem,5vw,4rem);line-height:1.05;margin:10px 0 14px;font-weight:900;">See the product in real environments.</h1>
    <p style="color:rgba(255,255,255,.76);line-height:1.8;font-size:1.05rem;">A quick look at LounGenie in operation across hospitality and poolside use cases.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:0 0 78px;background:#f4f8fb;">
  <div class="lgx-shell" style="margin-top:-24px;display:flex;flex-direction:column;gap:20px;">
    <div class="lgx-card" style="padding:20px;"><figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=EZ2CfBU30Ho</div></figure></div>
    <div class="lgx-grid-2">
      <div class="lgx-card" style="padding:18px;"><figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=M48NYM06JgY</div></figure></div>
      <div class="lgx-card" style="padding:18px;"><figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=PhV1JVo9POI</div></figure></div>
    </div>
    <div class="lgx-grid-2">
      <div class="lgx-card" style="padding:18px;"><figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=3Rjba7pWs_I</div></figure></div>
      <div class="lgx-card" style="padding:18px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0d1b2a,#123559);color:#fff;"><div style="max-width:360px;text-align:center;"><p class="lgx-kicker" style="color:#8ad8ff;margin:0 0 10px;">Installations</p><h3 style="margin:0 0 10px;font-size:1.7rem;">From resort cabanas to water parks.</h3><p style="margin:0;color:rgba(255,255,255,.72);line-height:1.8;">The product is designed to feel premium in a wide range of guest-facing environments.</p></div></div>
    </div>
  </div>
</section>
<!-- /wp:html -->
"""


def gallery_content():
    return GLOBAL_STYLE + f"""
<!-- wp:html -->
<section style="padding:76px 0 48px;background:#fff;">
  <div class="lgx-narrow" style="text-align:center;">
    <p class="lgx-kicker">Gallery</p>
    <h1 style="font-size:clamp(2.1rem,5vw,4rem);line-height:1.05;margin:10px 0 14px;color:#0d1b2a;font-weight:900;">Installation imagery that matches a premium product.</h1>
    <p class="lgx-muted" style="font-size:1.05rem;line-height:1.8;">Selected real-world images from hotels, resorts, and water parks where LounGenie integrates naturally into the guest environment.</p>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section style="padding:0 0 78px;background:#f4f8fb;">
  <div class="lgx-shell">
    <div class="lgx-grid-3">
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['hil_aloha']}" alt="Hilton resort installation"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['hil_daybed']}" alt="Hilton daybed installation"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['grove']}" alt="The Grove resort installation"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['cowa1']}" alt="Cowabunga premium seating"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['cowa2']}" alt="Water park cabana"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['cowa3']}" alt="Poolside cabana safe"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['cowa4']}" alt="Cabana interior smart unit"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['grove_soft']}" alt="Resort comfort setup"></div>
      <div class="lgx-media" style="aspect-ratio:16/13;"><img src="{IMG['sea']}" alt="SeaWorld installation"></div>
    </div>
  </div>
</section>
<!-- /wp:html -->
"""


def post_json(url, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(url, method="POST", data=data, headers={**HEADERS, "Content-Length": str(len(data))})
    with urllib.request.urlopen(req, timeout=60) as r:
        return json.loads(r.read())


def update_page(page_id, content):
    meta = PAGE_DATA[page_id]
    try:
        result = post_json(
            f"{PAGES}/{page_id}",
            {
                "title": meta["title"],
                "excerpt": meta["excerpt"],
                "featured_media": meta["featured_media"],
                "content": content,
                "status": "publish",
                "template": "page-wide",
            },
        )
        return True, result.get("link", "")
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}: {e.read().decode()[:240]}"
    except Exception as e:
        return False, str(e)


PAGES_TO_UPDATE = {
    4701: home_content,
    2989: features_content,
    4862: about_content,
    5139: contact_content,
    5285: videos_content,
    5223: gallery_content,
}

print("=" * 72)
print("LounGenie v8 | Modern Sales Page Refresh")
print("=" * 72)

for page_id, fn in PAGES_TO_UPDATE.items():
    ok, out = update_page(page_id, fn())
    print(f"{'✓' if ok else '✗'} {page_id} {out}")

print("Done.")
print("- Sales pages refreshed with a more modern smart-product visual system.")
print("- Investor pages left structurally intact from the previous pass.")
