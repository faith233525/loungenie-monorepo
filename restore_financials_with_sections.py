import urllib.request, json, base64, re, html as htmllib
from collections import defaultdict

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred, "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5686

# ── Fetch original revision that has correct structure (5693) ─────────────────
req = urllib.request.Request(f"{base}/pages/{page_id}/revisions/5693", headers=hdrs)
with urllib.request.urlopen(req) as r:
    rev5693 = json.loads(r.read())
orig = rev5693["content"]["rendered"]

# Parse sections from original: split on h2 tags
sections_orig = re.split(r'(<h2[^>]*>.*?</h2>)', orig, flags=re.IGNORECASE|re.DOTALL)

# Build map: section_title -> list of (anchor, url)
section_docs = {}  # title -> [(anchor, url)]
current_title = None
for part in sections_orig:
    if re.match(r'<h2', part, re.IGNORECASE):
        current_title = htmllib.unescape(re.sub(r'<[^>]+>', '', part).strip())
        section_docs[current_title] = []
    elif current_title:
        links = re.findall(
            r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\'][^>]*>(.*?)</a>',
            part, re.IGNORECASE|re.DOTALL
        )
        for url, anchor_raw in links:
            anchor = htmllib.unescape(re.sub(r'<[^>]+>', '', anchor_raw).strip())
            section_docs[current_title].append((anchor, url))

# ── Add 2026 AGM section (from revision 8627) ─────────────────────────────────
# These are new 2026 docs not in the 5693 revision
section_docs["2026 Annual General Meeting"] = [
    ("Notice of Meeting & Management Information Circular",
     "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf"),
    ("Form of Proxy",
     "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Form-of-Proxy_Common-Shares-Final.pdf"),
]

# ── Fix truncated label ────────────────────────────────────────────────────────
for title in section_docs:
    fixed = []
    for anchor, url in section_docs[title]:
        if anchor == "MD&A \u2013 September 30, 202":
            anchor = "MD&A \u2013 September 30, 2024"
        fixed.append((anchor, url))
    section_docs[title] = fixed

# ── Define ordered section list ───────────────────────────────────────────────
# 2026 AGM first, then rest in reverse chron order from original
ordered_sections = [
    "2026 Annual General Meeting",
    "2025 Financial Reports",
    "2025 Annual General Meeting",
    "2024 Financial Reports",
    "2023 Financial Reports",
    "2022 Financial Reports",
    "2021 Financial Reports",
    "2020 Financial Reports",
    "2019 Financial Reports",
    "2018 Financial Reports",
    "2017 Financial Reports",
    "2016 Financial Reports",
]

# ── Generate section HTML ──────────────────────────────────────────────────────
def make_section(title, docs):
    rows = []
    for anchor, url in docs:
        rows.append(
            f'<tr>'
            f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;">'
            f'<a href="{url}" target="_blank" rel="noopener noreferrer" '
            f'style="color:#1a73e8;text-decoration:none;font-weight:500;">'
            f'{anchor}</a>'
            f'</td>'
            f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;text-align:center;white-space:nowrap;">'
            f'<a href="{url}" target="_blank" rel="noopener noreferrer" '
            f'style="background:#1a73e8;color:#fff;padding:4px 14px;border-radius:4px;text-decoration:none;font-size:13px;">Download PDF</a>'
            f'</td>'
            f'</tr>'
        )
    rows_html = "\n".join(rows)
    return f"""
<h2 style="background:#0a2a4a;color:#fff;padding:10px 20px;margin:36px 0 0;border-radius:6px 6px 0 0;font-size:15px;font-weight:700;">{title}</h2>
<table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #dde3ea;border-top:none;border-radius:0 0 6px 6px;margin-bottom:4px;">
  <thead>
    <tr style="background:#f0f4f8;">
      <th style="padding:9px 16px;text-align:left;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;">Document</th>
      <th style="padding:9px 16px;text-align:center;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;width:130px;">PDF</th>
    </tr>
  </thead>
  <tbody>
{rows_html}
  </tbody>
</table>"""

all_sections_html = ""
for title in ordered_sections:
    docs = section_docs.get(title, [])
    if docs:
        all_sections_html += make_section(title, docs)

total = sum(len(v) for v in section_docs.values())

page_content = f"""<!-- wp:html -->
<style>
  .fin-page-wrap {{ max-width:960px; margin:0 auto; padding:0 20px 60px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }}
  .fin-hero {{ background:linear-gradient(135deg,#0a2a4a 0%,#1a5276 100%); color:#fff; padding:60px 40px; border-radius:12px; margin-bottom:40px; text-align:center; }}
  .fin-hero h1 {{ font-size:36px; font-weight:800; margin:0 0 12px; }}
  .fin-hero p {{ font-size:17px; margin:0 auto; opacity:.85; max-width:680px; }}
  .fin-intro {{ background:#f8f9fa; border-left:4px solid #1a73e8; padding:18px 24px; border-radius:0 8px 8px 0; margin-bottom:8px; font-size:15px; color:#444; }}
</style>
<div class="fin-page-wrap">

  <div class="fin-hero">
    <h1>Investor Information &mdash;<br>The LounGenie &amp; Pool Safe Inc.</h1>
    <p>Financial statements, MD&amp;A reports, and AGM filings for Pool-Safe Innovations Inc. All filings as submitted to SEDAR.</p>
  </div>

  <h2 style="font-size:22px;color:#0a2a4a;margin:0 0 8px;font-weight:700;">Financial Reports</h2>
  <div class="fin-intro">
    <strong>Total filings: {total}</strong> &mdash; Quarterly financial statements and MD&amp;A reports from 2016 through 2025, plus Annual General Meeting documents. Click any document title or Download PDF to open.
  </div>

{all_sections_html}

  <p style="text-align:center;color:#888;font-size:13px;margin-top:40px;">
    Pool-Safe Innovations Inc. &bull; All filings as submitted to SEDAR &bull; Documents open in a new tab
  </p>
</div>
<!-- /wp:html -->"""

print(f"Generated content: {len(page_content)} chars")
print(f"Total documents: {total}")
for title in ordered_sections:
    docs = section_docs.get(title, [])
    if docs:
        print(f"  {title}: {len(docs)} docs")

# Push to WordPress
payload = json.dumps({"content": page_content}).encode()
req2 = urllib.request.Request(f"{base}/pages/{page_id}", data=payload, headers=hdrs, method="POST")
with urllib.request.urlopen(req2) as r:
    result = json.loads(r.read())
print(f"\nDone! Content length: {len(result['content']['rendered'])}")
print(f"URL: {result['link']}")
