import urllib.request
import urllib.parse
import json
import base64
import re
import html

user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
credentials = base64.b64encode(f"{user}:{app_password}".encode()).decode()
headers = {"Authorization": f"Basic {credentials}", "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5686

# ── Fetch revision 5693 (77 properly-labelled links) ──────────────────────────
req = urllib.request.Request(f"{base}/pages/{page_id}/revisions/5693", headers=headers)
with urllib.request.urlopen(req) as resp:
    rev = json.loads(resp.read())
content_5693 = rev["content"]["rendered"]
print(f"Rev 5693 content length: {len(content_5693)}")

# Extract all PDF links from revision 5693
link_matches_5693 = re.findall(
    r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\'][^>]*>(.*?)</a>',
    content_5693, re.IGNORECASE | re.DOTALL
)
print(f"Rev 5693 PDF links found: {len(link_matches_5693)}")

# ── 2026 AGM documents (new from revision 8627) ───────────────────────────────
new_2026_docs = [
    ("Notice of Meeting &amp; Management Information Circular – 2026 AGM",
     "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf"),
    ("Form of Proxy – 2026 AGM",
     "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Form-of-Proxy_Common-Shares-Final.pdf"),
]

# ── Build the complete organised document list ────────────────────────────────
# Collect all documents from rev 5693 (decoded anchor text -> url)
all_docs = []

# First add 2026 AGM docs
for anchor, url in new_2026_docs:
    all_docs.append((html.unescape(anchor), url))

# Add all docs from rev 5693
for url, anchor_raw in link_matches_5693:
    anchor = html.unescape(re.sub(r'<[^>]+>', '', anchor_raw).strip())
    all_docs.append((anchor, url))

# ── Format documents into year-grouped sections ───────────────────────────────
def year_from_doc(anchor, url):
    # Extract year from anchor text (e.g. "Financials – September 30, 2025")
    m = re.search(r'\b(20\d{2})\b', anchor)
    if m:
        return int(m.group(1))
    # Try URL
    m = re.search(r'/(20\d{2})/', url)
    if m:
        return int(m.group(1))
    return 0

def section_key(anchor):
    """Returns (year, quarter_sort) for ordering within a year."""
    year = year_from_doc(anchor, "")
    # AGM docs last within a year
    if 'AGM' in anchor or 'Notice of Meeting' in anchor or 'Circular' in anchor or 'Form of Proxy' in anchor or 'Request Form' in anchor:
        return (year, 99)
    # Quarters
    dates = {
        'december 31': 4, 'dec 31': 4,
        'september 30': 3, 'sept 30': 3,
        'june 30': 2,
        'march 31': 1, 'mar 31': 1,
    }
    low = anchor.lower()
    for pattern, q in dates.items():
        if pattern in low:
            # Financials before MDA
            if 'financials' in low or 'financial s' in low:
                return (year, q * 10)
            else:
                return (year, q * 10 + 1)
    return (year, 50)

# group by year
from collections import defaultdict
by_year = defaultdict(list)
for anchor, url in all_docs:
    y = year_from_doc(anchor, url)
    by_year[y].append((anchor, url))

years_sorted = sorted(by_year.keys(), reverse=True)

# ── Generate HTML rows ────────────────────────────────────────────────────────
def make_rows(docs):
    rows = []
    for anchor, url in docs:
        icon = "📋" if any(k in anchor.lower() for k in ['notice', 'circular', 'proxy', 'request form']) else "📄"
        rows.append(
            f'<tr>'
            f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;">'
            f'<a href="{url}" target="_blank" rel="noopener noreferrer" '
            f'style="color:#1a73e8;text-decoration:none;font-weight:500;">'
            f'{icon} {anchor}</a>'
            f'</td>'
            f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;text-align:center;white-space:nowrap;">'
            f'<a href="{url}" target="_blank" rel="noopener noreferrer" '
            f'style="background:#1a73e8;color:#fff;padding:4px 12px;border-radius:4px;text-decoration:none;font-size:13px;">Download</a>'
            f'</td>'
            f'</tr>'
        )
    return "\n".join(rows)

year_sections = []
for y in years_sorted:
    docs = by_year[y]
    rows_html = make_rows(docs)
    year_section = f"""
<div style="margin-bottom:32px;">
  <h3 style="background:#0a2a4a;color:#fff;padding:10px 20px;margin:0 0 0 0;border-radius:6px 6px 0 0;font-size:16px;font-weight:700;">{y}</h3>
  <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #dde3ea;border-top:none;border-radius:0 0 6px 6px;overflow:hidden;">
    <thead>
      <tr style="background:#f0f4f8;">
        <th style="padding:10px 16px;text-align:left;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;">Document</th>
        <th style="padding:10px 16px;text-align:center;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;width:120px;">PDF</th>
      </tr>
    </thead>
    <tbody>
{rows_html}
    </tbody>
  </table>
</div>"""
    year_sections.append(year_section)

all_year_sections = "\n".join(year_sections)

# Total count
total_docs = sum(len(v) for v in by_year.values())

page_content = f"""<!-- wp:html -->
<style>
  .fin-page-wrap {{ max-width:960px; margin:0 auto; padding:0 20px 60px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }}
  .fin-hero {{ background:linear-gradient(135deg,#0a2a4a 0%,#1a5276 100%); color:#fff; padding:60px 40px; border-radius:12px; margin-bottom:40px; text-align:center; }}
  .fin-hero h1 {{ font-size:36px; font-weight:800; margin:0 0 12px; }}
  .fin-hero p {{ font-size:17px; margin:0; opacity:.85; max-width:680px; margin:0 auto; }}
  .fin-intro {{ background:#f8f9fa; border-left:4px solid #1a73e8; padding:18px 24px; border-radius:0 8px 8px 0; margin-bottom:36px; font-size:15px; color:#444; }}
  .fin-toc {{ background:#fff; border:1px solid #dde3ea; border-radius:8px; padding:20px 24px; margin-bottom:36px; }}
  .fin-toc h4 {{ margin:0 0 10px; font-size:14px; text-transform:uppercase; letter-spacing:.5px; color:#888; }}
  .fin-toc a {{ display:inline-block; margin:4px 8px 4px 0; background:#e8f0fe; color:#1a73e8; padding:4px 12px; border-radius:20px; font-size:13px; text-decoration:none; font-weight:500; }}
  .fin-toc a:hover {{ background:#1a73e8; color:#fff; }}
</style>
<div class="fin-page-wrap">

  <div class="fin-hero">
    <h1>📊 Investor Financials</h1>
    <p>Financial statements, MD&amp;A reports, and AGM filings for Pool-Safe Innovations Inc. All filings as submitted to SEDAR.</p>
  </div>

  <div class="fin-intro">
    <strong>Total filings available: {total_docs}</strong> — Quarterly and annual financial statements and MD&amp;A reports from 2016 through 2025, plus meeting circulars. Click any document title or the Download button to open the PDF.
  </div>

  <div class="fin-toc">
    <h4>Jump to Year</h4>
    {"".join(f'<a href="#year-{y}">{y}</a>' for y in years_sorted)}
  </div>

{all_year_sections}

  <p style="text-align:center;color:#888;font-size:13px;margin-top:40px;">
    Pool-Safe Innovations Inc. &bull; All filings submitted to SEDAR &bull; Documents open in a new tab
  </p>
</div>
<!-- /wp:html -->"""

print(f"\nGenerated page content: {len(page_content)} chars")
print(f"Total documents: {total_docs}")

# ── Push to WordPress ─────────────────────────────────────────────────────────
payload = json.dumps({"content": page_content}).encode("utf-8")
req = urllib.request.Request(
    f"{base}/pages/{page_id}",
    data=payload,
    headers=headers,
    method="POST"
)
with urllib.request.urlopen(req) as resp:
    result = json.loads(resp.read())

print(f"\n✅ Page updated!")
print(f"   ID: {result['id']}")
print(f"   Status: {result['status']}")
print(f"   Title: {result['title']['rendered']}")
print(f"   Content length: {len(result['content']['rendered'])} chars")
print(f"   URL: {result['link']}")
