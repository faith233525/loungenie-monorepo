#!/usr/bin/env python3
"""Apply Gutenberg-only content updates across staging marketing pages.

Pages updated:
- About (4862)  [full Gutenberg body]
- Contact (5139) [full Gutenberg body]
- Videos (5285)  [full Gutenberg body]
- Home (4701), Features (2989), Gallery (5223) [link normalization only]

Investors page (5668) is intentionally untouched.
"""

import base64
import hashlib
import json
import os
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
# Use WP_AUTH env var. Accept either raw 'user:pass' or a base64 string.
WP_AUTH = os.environ.get('WP_AUTH')
if not WP_AUTH:
    raise SystemExit('Environment variable WP_AUTH not set; set to "user:pass" or base64 string')
if ':' in WP_AUTH:
    AUTH = base64.b64encode(WP_AUTH.encode()).decode()
else:
    AUTH = WP_AUTH
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}

STAGING = 'https://loungenie.com/staging'
IMG = f'{STAGING}/wp-content/uploads/2026/03'

ABOUT_CONTENT = f'''<!-- wp:cover {{"url":"{IMG}/lg-about-westin-hilton-head-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.5,"y":0.36}},"isDark":true,"className":"lg9-page-hero"}} -->
<div class="wp-block-cover is-dark lg9-page-hero" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hospitality Innovation" src="{IMG}/lg-about-westin-hilton-head-scaled.jpg" style="object-position:50% 36%" data-object-fit="cover" data-object-position="50% 36%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1080px"}}}} -->
<div class="wp-block-group"><!-- wp:heading {{"level":1,"style":{{"typography":{{"fontSize":"56px","lineHeight":"1.02"}}}}}} -->
<h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Hospitality Innovation</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {{"style":{{"typography":{{"fontSize":"20px","lineHeight":"1.8"}}}}}} -->
<p style="font-size:20px;line-height:1.8">Built in Canada by PoolSafe to help operators improve guest experience, service speed, and poolside revenue.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph --><p>Why It Works</p><!-- /wp:paragraph -->

<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">A practical operating tool, not just another cabana accessory.</h2><!-- /wp:heading -->

<!-- wp:paragraph --><p>LounGenie is purpose-built for hospitality operations. It combines service enablement and guest convenience at the seat, where it matters most.</p><!-- /wp:paragraph -->

<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">ORDER</h3><!-- /wp:heading --><!-- wp:paragraph --><p>QR ordering sends requests to a dedicated PoolSafe printer for staff execution.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">STASH</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Waterproof safe with waterproof keypad for secure guest storage.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">CHARGE + CHILL</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Solar USB charging and a removable ice bucket for all-day comfort.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Tier structure for every property type</h2><!-- /wp:heading -->

<!-- wp:list --><ul><li><strong>Classic:</strong> STASH + CHARGE + CHILL</li><li><strong>Service+:</strong> Classic + service-call button alerting staff touchscreen monitor</li><li><strong>2.0:</strong> Service+ + QR ordering (call button remains active)</li></ul><!-- /wp:list -->

<!-- wp:paragraph --><p>PoolSafe provides installation, maintenance, and service through a zero-upfront, revenue-share model.</p><!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/poolside-amenity-unit/">Explore Product Features</a></div><!-- /wp:button -->

<!-- wp:button {{"className":"is-style-outline"}} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{STAGING}/cabana-installation-photos/">View Installation Photos</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->'''

CONTACT_CONTENT = f'''<!-- wp:cover {{"url":"{IMG}/lg-contact-owc-cabana-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.52,"y":0.30}},"isDark":true,"className":"lg9-page-hero"}} -->
<div class="wp-block-cover is-dark lg9-page-hero" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Contact LounGenie" src="{IMG}/lg-contact-owc-cabana-scaled.jpg" style="object-position:52% 30%" data-object-fit="cover" data-object-position="52% 30%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1080px"}}}} -->
<div class="wp-block-group"><!-- wp:heading {{"level":1,"style":{{"typography":{{"fontSize":"56px","lineHeight":"1.02"}}}}}} -->
<h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Contact LounGenie</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {{"style":{{"typography":{{"fontSize":"20px","lineHeight":"1.8"}}}}}} -->
<p style="font-size:20px;line-height:1.8">Share your property goals and we will map the right deployment model for your cabanas and premium seating zones.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:info@poolsafeinc.com">Email Our Team</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"60px","bottom":"70px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group" style="padding-top:60px;padding-right:24px;padding-bottom:70px;padding-left:24px"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Start the conversation</h2><!-- /wp:heading -->

<!-- wp:paragraph --><p><strong>Email:</strong> <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><strong>Phone:</strong> <a href="tel:+14166302444">+1 (416) 630-2444</a></p><!-- /wp:paragraph -->

<!-- wp:paragraph --><p>Tell us your property type, seating layout, and service goals. We will recommend the right tier and rollout approach.</p><!-- /wp:paragraph -->

<!-- wp:list --><ul><li>Cabana and daybed placement recommendations</li><li>Tier fit: Classic, Service+, or 2.0</li><li>Operational setup for staff routing and support</li></ul><!-- /wp:list --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{IMG}/The-Grove-6.jpg" alt="LounGenie deployment at premium seating"/></figure><!-- /wp:image --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/poolside-amenity-unit/">Review Product Features</a></div><!-- /wp:button -->

<!-- wp:button {{"className":"is-style-outline"}} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{STAGING}/loungenie-videos/">Watch Deployment Videos</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->'''

VIDEOS_CONTENT = f'''<!-- wp:cover {{"url":"{IMG}/lg-gallery-sea-world-san-diego.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.52,"y":0.44}},"isDark":true,"className":"lg9-page-hero"}} -->
<div class="wp-block-cover is-dark lg9-page-hero" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie Videos" src="{IMG}/lg-gallery-sea-world-san-diego.jpg" style="object-position:52% 44%" data-object-fit="cover" data-object-position="52% 44%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1080px"}}}} -->
<div class="wp-block-group"><!-- wp:heading {{"level":1,"style":{{"typography":{{"fontSize":"56px","lineHeight":"1.02"}}}}}} -->
<h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">LounGenie Videos</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {{"style":{{"typography":{{"fontSize":"20px","lineHeight":"1.8"}}}}}} -->
<p style="font-size:20px;line-height:1.8">See the unit in real hospitality environments and understand how it performs in daily operations.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">What to look for in each clip</h2><!-- /wp:heading -->

<!-- wp:list --><ul><li>How LounGenie fits into cabanas, daybeds, and premium seating</li><li>Guest flow: QR ordering, service requests, and in-seat convenience</li><li>Operational readiness across different venue types</li></ul><!-- /wp:list -->

<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{IMG}/six-flags-hurricane-harbor-cabana.jpg" alt="Six Flags Hurricane Harbor deployment overview"/></figure><!-- /wp:image --><!-- wp:paragraph --><p><strong>Waterpark deployment</strong><br>Cabana placement and guest seating context.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{IMG}/lg-gallery-water-world-cabana-1.jpg" alt="Water World deployment overview"/></figure><!-- /wp:image --><!-- wp:paragraph --><p><strong>Resort-style deployment</strong><br>Service-side positioning and guest interaction zones.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:paragraph --><p>Need a custom walkthrough for your property layout? We can provide a focused demo session for your team.</p><!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{STAGING}/contact-loungenie/">Book a Demo Session</a></div><!-- /wp:button -->

<!-- wp:button {{"className":"is-style-outline"}} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{STAGING}/cabana-installation-photos/">Browse Installation Photos</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->'''


def get_page_content(page_id: int) -> str:
    r = requests.get(f'{BASE}/pages/{page_id}?context=edit', headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')


def update_page(page_id: int, content: str) -> None:
    payload = json.dumps({'content': content, 'status': 'publish'})
    r = requests.post(f'{BASE}/pages/{page_id}', headers=HEADERS, data=payload, timeout=60)
    if r.status_code not in (200, 201):
        raise SystemExit(f'Update failed for {page_id}: HTTP {r.status_code} {r.text[:300]}')


def normalize_links(content: str) -> str:
    replacements = {
        'https://www.loungenie.com/index.php/contact-loungenie/': f'{STAGING}/contact-loungenie/',
        'https://www.loungenie.com/contact-loungenie/': f'{STAGING}/contact-loungenie/',
        'https://www.loungenie.com/poolside-amenity-unit/': f'{STAGING}/poolside-amenity-unit/',
        'https://www.loungenie.com/cabana-installation-photos/': f'{STAGING}/cabana-installation-photos/',
        'https://www.loungenie.com/hospitality-innovation/': f'{STAGING}/hospitality-innovation/',
        'https://www.loungenie.com/loungenie-videos/': f'{STAGING}/loungenie-videos/',
    }
    updated = content
    for old, new in replacements.items():
        updated = updated.replace(old, new)
    return updated


def sha256(text: str) -> str:
    return hashlib.sha256(text.encode()).hexdigest()


def main() -> None:
    # Safety check for Investors before updates.
    investors_before = get_page_content(5668)
    investors_hash_before = sha256(investors_before)

    # Full Gutenberg bodies.
    update_page(4862, ABOUT_CONTENT)
    print('UPDATED about (4862)')
    update_page(5139, CONTACT_CONTENT)
    print('UPDATED contact (5139)')
    update_page(5285, VIDEOS_CONTENT)
    print('UPDATED videos (5285)')

    # Link normalization on already-good Gutenberg pages.
    for page_id, name in [(4701, 'home'), (2989, 'features'), (5223, 'gallery')]:
        current = get_page_content(page_id)
        fixed = normalize_links(current)
        if fixed != current:
            update_page(page_id, fixed)
            print(f'UPDATED {name} links ({page_id})')
        else:
            print(f'NOOP {name} links ({page_id})')

    # Ensure investors stayed untouched.
    investors_after = get_page_content(5668)
    investors_hash_after = sha256(investors_after)
    print('INVESTORS_HASH_BEFORE', investors_hash_before)
    print('INVESTORS_HASH_AFTER ', investors_hash_after)
    print('INVESTORS_UNCHANGED', investors_hash_before == investors_hash_after)


if __name__ == '__main__':
    main()
