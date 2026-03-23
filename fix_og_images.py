#!/usr/bin/env python3
"""Fix OG images on Videos, Financials, Press, About pages by setting featured_media."""
import requests, base64, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

# Page ID → media ID to set as featured image (Rank Math will use for OG)
assignments = {
    5285: {'name': 'videos',     'media_id': 7249,  'desc': 'Screenshot-2026-03-11-210110.webp — LounGenie video overview'},
    5686: {'name': 'financials', 'media_id': 8362,  'desc': 'mc-mcowc-16683_Classic-Hor.jpg — premium cabana installation'},
    5716: {'name': 'press',      'media_id': 6849,  'desc': 'Sea-World-San-Diego-Edited.webp — real property photo'},
    4862: {'name': 'about',      'media_id': 8380,  'desc': 'Hilton-Aloha-Falls-Cabana-2-scaled.jpg — resort setting'},
}

print("Setting featured_media for OG image fix:")
for pid, info in assignments.items():
    r = requests.post(f'{BASE}/pages/{pid}', headers=hdrs,
                      data=json.dumps({'featured_media': info['media_id']}), timeout=20)
    if r.status_code in (200, 201):
        d = r.json()
        fm = d.get('featured_media')
        print(f"  ✓ {info['name']} ({pid}): featured_media set to {fm} [{info['desc']}]")
    else:
        print(f"  ✗ {info['name']} ({pid}): HTTP {r.status_code} — {r.text[:200]}")

print("\nDone — Rank Math will now auto-use these images as OG/social share images.")
print("Note: OG images will appear in rendered source as og:image meta tags.")
