import urllib.request
import json
import base64

USER = 'admin'
PW = 'i6IM cqLZ vQDC pIRk nKFr g35i'
AUTH = 'Basic ' + base64.b64encode((USER + ':' + PW).encode()).decode()
HEADERS = {'Authorization': AUTH, 'Content-Type': 'application/json'}
PAGE_ID = 5223
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

content = '''<!-- wp:html -->
<div class="lgx-gallery" style="font-family:Manrope,Arial,sans-serif;color:#0b1726;background:#f4f8fb;">
  <section style="position:relative;min-height:56vh;display:flex;align-items:flex-end;overflow:hidden;background:#081423;">
    <img src="https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-6.jpg" alt="LounGenie installed in premium cabanas" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.48;" loading="eager" fetchpriority="high">
    <div style="position:absolute;inset:0;background:linear-gradient(100deg,rgba(4,12,28,.9) 0%,rgba(4,35,68,.66) 58%,rgba(0,75,147,.35) 100%);"></div>
    <div style="position:relative;z-index:2;max-width:1280px;width:100%;margin:0 auto;padding:64px 24px 52px;">
      <p style="margin:0 0 12px;color:#8dd6ff;font-weight:800;letter-spacing:2px;text-transform:uppercase;font-size:12px;">Real Installations</p>
      <h1 style="margin:0;color:#fff;font-size:clamp(2rem,4vw,3.6rem);line-height:1.06;letter-spacing:-1px;max-width:860px;">LounGenie Gallery: Cabanas, Daybeds, Clamshells, and Lock Detail Views</h1>
      <p style="margin:16px 0 0;color:rgba(255,255,255,.9);max-width:780px;line-height:1.75;">A sharper collection of live installs with better framing so the full unit and lock panel stay visible.</p>
    </div>
  </section>

  <section style="max-width:1280px;margin:0 auto;padding:36px 24px 8px;">
    <div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:18px;">
      <article style="grid-column:span 8;background:#fff;border:1px solid #d8e4ee;border-radius:18px;overflow:hidden;box-shadow:0 14px 30px rgba(13,27,42,.08);">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.jpg" alt="Cabana row with LounGenie units" style="width:100%;height:360px;object-fit:cover;display:block;" loading="eager">
        <div style="padding:18px 20px 22px;">
          <h2 style="margin:0 0 8px;font-size:1.25rem;">Resort-Scale Cabana Deployment</h2>
          <p style="margin:0;color:#2f455c;line-height:1.7;">Wide-angle installation shot showing multiple premium seats equipped with LounGenie.</p>
        </div>
      </article>
      <article style="grid-column:span 4;background:#fff;border:1px solid #d8e4ee;border-radius:18px;overflow:hidden;box-shadow:0 14px 30px rgba(13,27,42,.08);">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2090-scaled.jpeg" alt="LounGenie lock panel and bucket detail" style="width:100%;height:360px;object-fit:contain;background:#eef4f9;display:block;" loading="eager">
        <div style="padding:18px 20px 22px;">
          <h2 style="margin:0 0 8px;font-size:1.15rem;">Lock Panel In View</h2>
          <p style="margin:0;color:#2f455c;line-height:1.7;">Close-up composition emphasizing the current lock hardware and service-ready layout.</p>
        </div>
      </article>
    </div>
  </section>

  <section style="max-width:1280px;margin:0 auto;padding:8px 24px 26px;">
    <h2 style="margin:8px 0 14px;font-size:1.7rem;">Feature Detail Set (No Repeats)</h2>
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
      <figure style="margin:0;background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2080.jpeg" alt="STASH waterproof safe with lock panel" style="width:100%;height:250px;object-fit:contain;background:#f1f6fb;display:block;" loading="eager">
        <figcaption style="padding:10px 12px;color:#345;">STASH: waterproof safe access</figcaption>
      </figure>
      <figure style="margin:0;background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2081.jpeg" alt="Waterproof keypad and lock interface" style="width:100%;height:250px;object-fit:contain;background:#f1f6fb;display:block;" loading="eager">
        <figcaption style="padding:10px 12px;color:#345;">Waterproof keypad and lock</figcaption>
      </figure>
      <figure style="margin:0;background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2078-scaled.jpeg" alt="Charge and lock zone near guest seating" style="width:100%;height:250px;object-fit:contain;background:#f1f6fb;display:block;" loading="eager">
        <figcaption style="padding:10px 12px;color:#345;">CHARGE area with lock visibility</figcaption>
      </figure>
      <figure style="margin:0;background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2079-scaled.jpeg" alt="Safe door and panel detail in cabana install" style="width:100%;height:250px;object-fit:contain;background:#f1f6fb;display:block;" loading="eager">
        <figcaption style="padding:10px 12px;color:#345;">Safe door and panel detail</figcaption>
      </figure>
    </div>
  </section>

  <section style="max-width:1280px;margin:0 auto;padding:0 24px 42px;">
    <h2 style="margin:0 0 14px;font-size:1.7rem;">Property Highlights</h2>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;">
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-6.jpg" alt="Waterpark premium seating install" style="width:100%;height:220px;object-fit:cover;display:block;" loading="lazy">
      </article>
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/Sea-World-San-Diego.jpg" alt="Waterpark cabana with LounGenie" style="width:100%;height:220px;object-fit:cover;display:block;" loading="lazy">
      </article>
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-Cabana-1.jpg" alt="Family waterpark cabana with LounGenie" style="width:100%;height:220px;object-fit:cover;display:block;" loading="lazy">
      </article>
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2025/10/Water-World-Cabana-2.jpg" alt="Premium cabana row and daybeds" style="width:100%;height:220px;object-fit:cover;display:block;" loading="lazy">
      </article>
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/The-Grove-7-scaled.jpg" alt="Pool deck premium seating section" style="width:100%;height:220px;object-fit:cover;display:block;" loading="lazy">
      </article>
      <article style="background:#fff;border:1px solid #d8e4ee;border-radius:14px;overflow:hidden;">
        <img src="https://www.loungenie.com/wp-content/uploads/2026/03/IMG_2089-scaled.jpeg" alt="LounGenie lock panel and removable bucket in context" style="width:100%;height:220px;object-fit:contain;background:#eff5fa;display:block;" loading="lazy">
      </article>
    </div>
  </section>

  <section style="max-width:1280px;margin:0 auto;padding:0 24px 58px;">
    <div style="background:linear-gradient(130deg,#062142 0%,#004b93 52%,#00a8dd 100%);border-radius:18px;padding:28px 24px;color:#fff;display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
      <div>
        <p style="margin:0 0 6px;opacity:.85;text-transform:uppercase;letter-spacing:1.8px;font-size:12px;">See More Angles</p>
        <h3 style="margin:0;font-size:1.6rem;line-height:1.2;">Want location-specific galleries and lock-detail walkthroughs?</h3>
      </div>
      <a href="https://www.loungenie.com/contact-loungenie/" style="display:inline-block;text-decoration:none;background:#fff;color:#003e7a;font-weight:800;padding:13px 18px;border-radius:11px;">Book a Live Demo</a>
    </div>
  </section>
</div>
<!-- /wp:html -->
'''

payload = json.dumps({'content': content}).encode()
req = urllib.request.Request(f'{BASE}/pages/{PAGE_ID}', data=payload, headers=HEADERS, method='POST')
with urllib.request.urlopen(req, timeout=45) as r:
    out = json.loads(r.read())

print('updated', out.get('status'), 'len', len(out['content']['rendered']))
