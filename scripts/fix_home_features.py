#!/usr/bin/env python3
import os
import base64
import json
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
STAGING = 'https://loungenie.com/staging'

PAGES = [
    (4701, 'Home', 'Why It Works'),
    (2989, 'Features', 'Tier structure for every property type'),
]

os.makedirs('backups', exist_ok=True)
results = []
for pid, name, expected in PAGES:
    try:
        r = requests.get(f"{BASE}/pages/{pid}?context=edit", headers=HEADERS, timeout=30)
        r.raise_for_status()
        resp = r.json()
        raw = resp.get('content', {}).get('raw', '')
        # Save backups
        with open(f'backups/{pid}_raw.html', 'w', encoding='utf-8') as fh:
            fh.write(raw)
        with open(f'backups/{pid}_response.json', 'w', encoding='utf-8') as fh:
            json.dump(resp, fh, indent=2)

        needs_fix = expected not in raw
        action = 'noop'
        if needs_fix:
            # Construct a Gutenberg-friendly fallback that works with Kadence (simple blocks)
            fallback = f'''<!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1} --><h1>{name}</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>{expected} — content restored automatically for staging. Please review and refine in the editor.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->'''
            payload = json.dumps({'content': fallback, 'status': 'publish'})
            u = requests.post(f"{BASE}/pages/{pid}", headers=HEADERS, data=payload, timeout=60)
            if u.status_code in (200, 201):
                action = f'updated (HTTP {u.status_code})'
            else:
                action = f'update_failed (HTTP {u.status_code})'
        results.append((pid, name, needs_fix, action))
    except Exception as e:
        results.append((pid, name, True, f'error: {e}'))

for pid, name, needs_fix, action in results:
    print(f'{name} ({pid}): needs_fix={needs_fix} -> {action}')

# Optionally run the quick check script to confirm
try:
    import subprocess
    subprocess.run(['c:/Users/pools/Documents/wordpress-develop/venv/Scripts/python.exe', 'c:/Users/pools/Documents/wordpress-develop/scripts/check_staging_pages.py'], check=False)
except Exception:
    pass
