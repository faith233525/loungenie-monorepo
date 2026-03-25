#!/usr/bin/env python3
"""Inventory all real install photos for gallery expansion."""
import requests, base64

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

# Get all media across all pages
all_media = []
page = 1
while True:
    r = requests.get(f'{BASE}/media', headers=hdrs,
                     params={'per_page': 100, 'orderby': 'date', 'order': 'desc', 'page': page}, 
                     timeout=30)
    if r.status_code != 200:
        break
    items = r.json()
    if not items:
        break
    all_media.extend(items)
    if len(items) < 100:
        break
    page += 1

# Filter to real installation photos (exclude AI renders, logos, screenshots)
real_photos = []
skip_keywords = ['Gemini', 'Nano_Banana', 'wide_angle', 'Render_', 'Create_', 'Generate_',
                 'Use_Image', 'Use_the', 'IMAGE_TO_IMAGE', 'Photorealistic', 'Low_angle',
                 'Hero_seed', 'A_high_fidelity', 'A_cinematic', 'Safe___guest',
                 'change_the_lounge', 'Gen4Turbo', 'on_the_safe', 'cropped-LounGenie',
                 'banner-', 'avbcmjmh', 'favicon', 'logo-', 'WWAlogo', 'iaapa_logo',
                 'IAAPA-Brass', 'Marriott_hotels', 'Screenshot-2025', 'gd.png',
                 'image_dcabb', '1714017', 'aa74e14a', 'Ritz-Carlton-Logo',
                 'the-ritz-carlton-logo', 'back.jpg', 'top.jpg', 'side.jpg', 'front-scaled',
                 'c.png', 'bucket.png', 'margaritaville', 'Carnival-Cruise']

for item in all_media:
    src = item.get('source_url', '')
    if not src:
        continue
    fname = src.split('/')[-1]
    mime = item.get('mime_type', '')
    if not mime.startswith('image/'):
        continue
    
    skip = any(kw in fname for kw in skip_keywords)
    if skip:
        continue
    
    # Only real photos: actual location shots, IMG_, DSC, water world, etc.
    w = item.get('media_details', {}).get('width', 0)
    h = item.get('media_details', {}).get('height', 0)
    alt = item.get('alt_text', '') or item.get('title', {}).get('rendered', '')
    date = item.get('date', '')[:10]
    iid = item.get('id')
    
    real_photos.append({'id': iid, 'url': src, 'fname': fname, 'w': w, 'h': h, 'alt': alt, 'date': date})

print(f"Real installation photos found: {len(real_photos)}")
print()
for p in real_photos:
    print(f"  ID={p['id']:6d} | {p['date']} | {p['w']:4d}x{p['h']:4d} | {p['fname'][:55]:<55} | {p['alt'][:50]}")
