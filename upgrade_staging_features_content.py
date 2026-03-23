#!/usr/bin/env python3
"""Upgrade missing/thin content on staging Features page (ID 2989)."""
import base64
import json
import re
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
PAGE_ID = 2989

REPLACEMENT_BLOCK = '''<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph --><p>Why Teams Choose LounGenie</p><!-- /wp:paragraph -->

<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Designed for real poolside operations, not showroom demos.</h1><!-- /wp:heading -->

<!-- wp:paragraph --><p>LounGenie combines guest convenience and staff efficiency in one commercial-grade amenity unit built for cabanas, daybeds, and premium seating zones.</p><!-- /wp:paragraph -->

<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg" alt="Westin Hilton Head premium seating with the LounGenie"/></figure><!-- /wp:image --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:list --><ul><li><strong>ORDER:</strong> Guest scans QR, and the order prints on a dedicated printer provided by PoolSafe.</li><li><strong>STASH:</strong> Waterproof safe with a waterproof keypad.</li><li><strong>CHARGE:</strong> Solar-powered USB charging ports.</li><li><strong>CHILL:</strong> Removable ice bucket for convenient in-seat refreshment service.</li></ul><!-- /wp:list --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Choose the right tier</h2><!-- /wp:heading -->

<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Classic</h3><!-- /wp:heading --><!-- wp:paragraph --><p>STASH + CHARGE + CHILL. A practical upgrade for premium seating with no complicated setup.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Service+</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Everything in Classic, plus a service call button that alerts staff on a dedicated touchscreen monitor.</p><!-- /wp:paragraph --></div><!-- /wp:column -->

<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">2.0</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Everything in Service+, plus QR ordering. The service button remains active for general service requests.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->

<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">How operations flow</h2><!-- /wp:heading -->

<!-- wp:list {"ordered":true} --><ol><li>Guest scans the QR code to place a food and beverage order.</li><li>Order prints on the dedicated PoolSafe printer for staff handling.</li><li>Service-call button alerts route separately to the staff touchscreen monitor.</li></ol><!-- /wp:list -->

<!-- wp:paragraph --><p><strong>No POS integration is required for guest QR ordering.</strong> PoolSafe handles installation, maintenance, and support under a zero-upfront, revenue-share model.</p><!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/">Request a Demo</a></div><!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/cabana-installation-photos/">View Installation Photos</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->'''


def get_page_content() -> str:
    resp = requests.get(f'{BASE}/pages/{PAGE_ID}?context=edit', headers=HEADERS, timeout=30)
    resp.raise_for_status()
    return resp.json().get('content', {}).get('raw', '')


def update_page_content(content: str) -> None:
    payload = json.dumps({'content': content, 'status': 'publish'})
    resp = requests.post(f'{BASE}/pages/{PAGE_ID}', headers=HEADERS, data=payload, timeout=45)
    if resp.status_code not in (200, 201):
        raise SystemExit(f'Update failed: HTTP {resp.status_code} {resp.text[:300]}')


def main() -> None:
    content = get_page_content()

    pattern = re.compile(
        r'<!-- wp:group \{"style":\{"spacing":\{"padding":\{"top":"72px".*?<!-- /wp:group -->\s*$',
        re.S,
    )

    updated, count = pattern.subn(REPLACEMENT_BLOCK, content, count=1)
    if count != 1:
        raise SystemExit('Expected Features body block not found; no update applied.')

    if updated == content:
        print('No changes needed.')
        return

    update_page_content(updated)
    print('Features page content upgraded on staging.')


if __name__ == '__main__':
    main()
