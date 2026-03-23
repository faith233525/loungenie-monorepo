#!/usr/bin/env python3
"""Upgrade missing/thin content on staging Gallery page (ID 5223)."""
import base64
import json
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}
PAGE_ID = 5223

OLD_PARA = '<!-- wp:paragraph --><p>All images below are native Gutenberg image blocks. You can replace, reorder, crop, and caption them directly in the editor.</p><!-- /wp:paragraph -->'
NEW_PARA = '<!-- wp:paragraph --><p>Explore real-world installations across resorts, waterparks, and premium seating zones. Each deployment view highlights how LounGenie integrates directly into cabanas and guest seating areas.</p><!-- /wp:paragraph -->'

INSERT_AFTER = '</figure><!-- /wp:gallery -->'
INSERT_BLOCK = '''</figure><!-- /wp:gallery -->

<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">What these deployments show</h2><!-- /wp:heading -->

<!-- wp:list --><ul><li>Flexible placement for private cabanas and premium seating sections</li><li>Consistent guest access to charging, secure storage, and refreshment features</li><li>Scalable rollout patterns across multiple units and venue zones</li></ul><!-- /wp:list -->

<!-- wp:paragraph --><p>Want a deployment plan for your property layout? We can map recommended placement by seating type, guest flow, and service zones.</p><!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/">Request a Deployment Plan</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'''


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
    updated = content

    if OLD_PARA in updated:
        updated = updated.replace(OLD_PARA, NEW_PARA)

    # Add the new explanatory section once.
    marker = 'What these deployments show'
    if marker not in updated and INSERT_AFTER in updated:
        updated = updated.replace(INSERT_AFTER, INSERT_BLOCK, 1)

    if updated == content:
        print('No changes needed.')
        return

    update_page_content(updated)
    print('Gallery page content upgraded on staging.')


if __name__ == '__main__':
    main()
