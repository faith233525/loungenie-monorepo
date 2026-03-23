#!/usr/bin/env python3
import requests
import base64
import json

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

pages = [5668, 5651, 5686, 5716]  # investors, board, financials, press

css = '''
<style id="lg-aa-plus-remaining">
/* Remaining pages A++ visual polish: readability, hierarchy, CTA emphasis */
.lg9-hero { min-height: 72vh !important; }
.lg9-hero .lg9-title-md, .lg9-hero h1 { color:#ffffff !important; text-shadow:0 1px 2px rgba(0,0,0,.32); }
.lg9-hero .lg9-copy, .lg9-hero p { color:#eef5ff !important; }
.lg9-copy, .lg9-copy p { color:#21384e !important; }
.lg9-title-md, h2 { color:#0b1f34 !important; letter-spacing:-0.01em; }

/* Financials/Press inline layout polish */
.page-id-5686 div[style*="max-width:960px"],
.page-id-5716 div[style*="max-width:900px"] {
  max-width: 1040px !important;
}
.page-id-5686 h1, .page-id-5716 h1 {
  font-size: clamp(2rem, 3.4vw, 2.6rem) !important;
  line-height: 1.15 !important;
}
.page-id-5686 p, .page-id-5716 p {
  line-height: 1.7 !important;
}

/* CTA priority */
.page-id-5651 a[href^="mailto:"], .page-id-5651 a[href^="tel:"],
.page-id-5668 a[href*="sedar"], .page-id-5668 a[href^="mailto:"],
.page-id-5686 a[href$=".pdf"],
.page-id-5716 a[href$=".pdf"] {
  display: inline-block;
  padding: 8px 12px;
  border-radius: 6px;
  background: #f2f7ff;
  border: 1px solid #d6e3f3;
  text-decoration: none !important;
  font-weight: 600;
  color: #10304d !important;
}

/* Better spacing rhythm */
.page-id-5668 h2, .page-id-5651 h2, .page-id-5686 h2, .page-id-5716 h2 {
  margin-top: 28px !important;
  margin-bottom: 10px !important;
}
</style>
'''

updated = []
for pid in pages:
    r = requests.get(f'{BASE}/pages/{pid}', headers=headers, timeout=30)
    r.raise_for_status()
    content = r.json().get('content', {}).get('rendered', '')

    if 'id="lg-aa-plus-remaining"' in content:
        continue

    end_style = content.find('</style>')
    if end_style != -1:
        new_content = content[:end_style + len('</style>')] + '\n' + css + content[end_style + len('</style>'):]
    else:
        new_content = css + '\n' + content

    u = requests.post(f'{BASE}/pages/{pid}', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
    if u.status_code in (200, 201):
        updated.append(pid)

print('UPDATED', updated)
