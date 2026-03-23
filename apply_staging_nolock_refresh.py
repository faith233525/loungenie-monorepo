#!/usr/bin/env python3
"""
Apply no-lock image refresh to staging site.
Targets: https://loungenie.com/staging/wp-json/wp/v2
- Features (2989): replace lock hardware images with Westin + Six Flags deployment images
- Gallery (5223): remove lock closeup images, add Six Flags deployment section
"""
import base64
import json
import re
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
S = 'https://loungenie.com/staging/wp-content/uploads/2026/03'
creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
headers = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}

# ── Staging image URLs ──────────────────────────────────────────────────────
WESTIN_HERO = f'{S}/lg-about-westin-hilton-head-scaled.jpg'
SF_CABANA   = f'{S}/six-flags-hurricane-harbor-cabana.jpg'
SF_ANGLE    = f'{S}/six-flags-hurricane-harbor-lineup-angle.jpg'
SF_FRONT    = f'{S}/six-flags-hurricane-harbor-lineup-front.jpg'
WW_CABANA   = f'{S}/lg-gallery-water-world-cabana-1.jpg'
COW_CABANA  = f'{S}/lg-gallery-cowabunga-cabana-1-scaled.jpg'
SOAKY       = f'{S}/lg-gallery-soaky-10-scaled.jpg'
HILTON_KONA = f'{S}/lg-gallery-hilton-kona-cabana-4-scaled.jpg'
SEA_WORLD   = f'{S}/lg-gallery-sea-world-san-diego.jpg'


# ── Features page replacements ──────────────────────────────────────────────
# IMG_3241 appears as cover background + column image (3 occurrences total via same URL)
FEATURES_MAP = [
    (
        'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3241-scaled-1.jpg',
        WESTIN_HERO,
    ),
    (
        'alt="LounGenie at cabana"',
        'alt="Westin Hilton Head premium seating with the LounGenie"',
    ),
    (
        'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3239-scaled-1.jpg',
        SF_CABANA,
    ),
    (
        'alt="Feature image 1"',
        'alt="Six Flags Hurricane Harbor private cabana deployment with LounGenie"',
    ),
    (
        'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3235-scaled-1.jpg',
        SF_ANGLE,
    ),
    (
        'alt="Feature image 2"',
        'alt="Six Flags Hurricane Harbor LounGenie units staged for deployment"',
    ),
    (
        'https://www.loungenie.com/wp-content/uploads/2026/03/IMG_3233-scaled-1.jpg',
        SF_FRONT,
    ),
    (
        'alt="Feature image 3"',
        'alt="Six Flags Hurricane Harbor current-production LounGenie units"',
    ),
]


# ── Gallery block rebuild ───────────────────────────────────────────────────
# Replaces the entire wp:gallery block with 8 clean deployment images.
GALLERY_IMAGES = [
    (SF_CABANA,   'Six Flags Hurricane Harbor private cabana deployment with LounGenie'),
    (SF_ANGLE,    'Six Flags Hurricane Harbor LounGenie units staged for deployment'),
    (SF_FRONT,    'Six Flags Hurricane Harbor current-production LounGenie units'),
    (WW_CABANA,   'Water World cabana with the LounGenie'),
    (COW_CABANA,  'Cowabunga Bay cabana with the LounGenie'),
    (SOAKY,       'Soaky Mountain premium seating with the LounGenie'),
    (HILTON_KONA, 'Hilton Waikoloa Kona pool cabana with the LounGenie'),
    (SEA_WORLD,   'Sea World San Diego premium seating with the LounGenie'),
]

IMG_BLOCKS = ''.join(
    f'<!-- wp:image --><figure class="wp-block-image">'
    f'<img src="{url}" alt="{alt}"/>'
    f'</figure><!-- /wp:image -->'
    for url, alt in GALLERY_IMAGES
)

NEW_GALLERY_BLOCK = (
    '<!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} -->'
    '<figure class="wp-block-gallery has-nested-images columns-3 is-cropped">'
    + IMG_BLOCKS +
    '</figure><!-- /wp:gallery -->'
)

GALLERY_PATTERN = re.compile(
    r'<!-- wp:gallery \{.*?\} -->.*?<!-- /wp:gallery -->',
    re.S,
)


def get_page(page_id):
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=headers, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')


def update_page(page_id, content):
    r = requests.post(
        f'{BASE}/pages/{page_id}',
        headers=headers,
        data=json.dumps({'content': content, 'status': 'publish'}),
        timeout=45,
    )
    if r.status_code not in (200, 201):
        raise SystemExit(f'Update failed HTTP {r.status_code}: {r.text[:220]}')


# ── Apply features update ───────────────────────────────────────────────────
print('--- Features (2989) ---')
features = get_page(2989)
updated = features
for old, new in FEATURES_MAP:
    if old in updated:
        updated = updated.replace(old, new)
        print(f'  replaced: {old[:60]}')
    else:
        print(f'  MISS:     {old[:60]}')

if updated == features:
    print('  no changes')
else:
    update_page(2989, updated)
    print('  UPDATED features')


# ── Apply gallery update ────────────────────────────────────────────────────
print('\n--- Gallery (5223) ---')
gallery = get_page(5223)
match = GALLERY_PATTERN.search(gallery)
if not match:
    print('  ERROR: gallery block not found')
else:
    old_block = match.group(0)
    updated_gallery = gallery.replace(old_block, NEW_GALLERY_BLOCK, 1)
    if updated_gallery == gallery:
        print('  no changes')
    else:
        update_page(5223, updated_gallery)
        print('  UPDATED gallery')
        print(f'  Replaced gallery block with {len(GALLERY_IMAGES)} deployment images')
