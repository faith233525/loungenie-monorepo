#!/usr/bin/env python3
"""Upgrade missing content on staging Home page (ID 4701)."""
import base64
import json
import re
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
PAGE_ID = 4701

NEW_SECTION = '''

<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph --><p>Built for Hospitality Teams</p><!-- /wp:paragraph -->

<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">One unit, four capabilities, real operational impact.</h2><!-- /wp:heading -->

<!-- wp:paragraph --><p>LounGenie is installed directly into cabanas and premium seating to improve guest convenience while creating new service and revenue opportunities.</p><!-- /wp:paragraph -->

<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">ORDER</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Guests scan a QR code, and orders print on a dedicated printer provided by PoolSafe.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">STASH</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Waterproof safe with a waterproof keypad for secure storage at the seat.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">CHARGE + CHILL</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Solar USB charging ports and a removable ice bucket for all-day comfort.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Three cumulative tiers</h3><!-- /wp:heading -->

<!-- wp:list --><ul><li><strong>Classic:</strong> STASH + CHARGE + CHILL</li><li><strong>Service+:</strong> Classic + service call button alerts to staff touchscreen monitor</li><li><strong>2.0:</strong> Service+ + QR ordering (service button remains active)</li></ul><!-- /wp:list -->

<!-- wp:paragraph --><p>PoolSafe handles installation, maintenance, and support through a zero-upfront revenue-share model.</p><!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/poolside-amenity-unit/">Explore Product Features</a></div><!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->
'''


def get_content() -> str:
    r = requests.get(f'{BASE}/pages/{PAGE_ID}?context=edit', headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')


def update_content(content: str) -> None:
    payload = json.dumps({'content': content, 'status': 'publish'})
    r = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=HEADERS, data=payload, timeout=45)
    if r.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {r.status_code} {r.text[:300]}')


def main() -> None:
    content = get_content()
    if 'Built for Hospitality Teams' in content:
        print('No changes needed.')
        return

    pattern = re.compile(
        r'<!-- wp:group \{"style":\{"spacing":\{"padding":\{"top":"56px".*?<!-- /wp:group -->',
        re.S,
    )
    match = pattern.search(content)
    if not match:
        raise SystemExit('Logo-strip group not found; no update applied.')

    updated = content[:match.end()] + NEW_SECTION + content[match.end():]
    update_content(updated)
    print('Home page content upgraded on staging.')


if __name__ == '__main__':
    main()
