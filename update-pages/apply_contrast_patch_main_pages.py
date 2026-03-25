#!/usr/bin/env python3
import requests
import base64
import json

BASE = 'https://www.loungenie.com/wp-json/wp/v2'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

# Main marketing pages only; do not touch investor content pages in this pass
pages = [4701, 2989, 4862, 5139, 5285, 5223]

patch_css = '''
<style id="lg-aa-plus-contrast">
/* A++ readability patch: stronger heading contrast and safer text rendering on mixed backgrounds */
.lg9-title, .lg9-title-md { color: #0b1f34 !important; letter-spacing: -0.02em; }
.lg9-copy, .lg9-copy p { color: #21384e !important; }
.lg9-kicker { color: #0073b8 !important; }
.lg9 [class*="hero"] .lg9-title,
.lg9 [class*="hero"] .lg9-title-md,
.lg9 [class*="hero"] h1,
.lg9 [class*="hero"] h2 { color: #ffffff !important; text-shadow: 0 1px 2px rgba(0,0,0,.32); }
.lg9 [class*="hero"] p,
.lg9 [class*="hero"] .lg9-copy,
.lg9 [class*="hero"] .lg9-hero-lead { color: #ecf4ff !important; text-shadow: 0 1px 1px rgba(0,0,0,.20); }
.lg9-card h2, .lg9-card h3, .lg9-card h4 { color: #10263d !important; }
.gx-head h2 { color: #0b1f34 !important; }
.gx-head p { color: #3c546b !important; font-weight: 600; }
</style>
'''

updated = []
for pid in pages:
    r = requests.get(f'{BASE}/pages/{pid}', headers=headers, timeout=30)
    r.raise_for_status()
    page = r.json()
    content = page.get('content', {}).get('rendered', '')

    if 'id="lg-aa-plus-contrast"' in content:
        continue

    # Insert after first style block for scope-local precedence
    end_style = content.find('</style>')
    if end_style != -1:
        new_content = content[:end_style + len('</style>')] + '\n' + patch_css + content[end_style + len('</style>'):]
    else:
        new_content = patch_css + '\n' + content

    u = requests.post(f'{BASE}/pages/{pid}', headers=headers, data=json.dumps({'content': new_content, 'status': 'publish'}), timeout=40)
    if u.status_code in (200, 201):
        updated.append(pid)
    else:
        print('FAILED', pid, u.status_code, u.text[:200])

print('UPDATED_PAGES', updated)
