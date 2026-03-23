import urllib.request
import json
import base64
import re

user = 'admin'
password = 'i6IM cqLZ vQDC pIRk nKFr g35i'
headers = {
    'Authorization': 'Basic ' + base64.b64encode((user + ':' + password).encode()).decode(),
    'Content-Type': 'application/json',
}
base = 'https://www.loungenie.com/wp-json/wp/v2'

page_ids = [4701, 2989, 4862, 5139, 5223, 5285, 5651, 5668, 5686, 5716]

# Insert no-lazy class + eager loading for img tags in custom page content

def patch_img_tag(match):
    tag = match.group(0)
    low = tag.lower()

    if 'litespeed-no-lazyload' in low:
        # Ensure eager loading is present
        if 'loading=' not in low:
            tag = tag[:-1] + ' loading="eager">'
        return tag

    # Add class attribute or append class value
    if 'class=' in low:
        tag = re.sub(
            r'class\s*=\s*"([^"]*)"',
            lambda m: 'class="' + m.group(1) + ' litespeed-no-lazyload skip-lazy no-lazyload"',
            tag,
            count=1,
            flags=re.I,
        )
    else:
        tag = tag[:-1] + ' class="litespeed-no-lazyload skip-lazy no-lazyload">'

    # Force eager load for reliability
    if 'loading=' not in tag.lower():
        tag = tag[:-1] + ' loading="eager">'

    # Preserve fetchpriority if absent
    if 'fetchpriority=' not in tag.lower():
        tag = tag[:-1] + ' fetchpriority="high">'

    return tag

for pid in page_ids:
    req = urllib.request.Request(f'{base}/pages/{pid}?context=edit', headers=headers)
    with urllib.request.urlopen(req, timeout=25) as r:
        page = json.loads(r.read())

    raw = page['content']['raw']
    img_count_before = len(re.findall(r'<img\b[^>]*>', raw, flags=re.I))

    updated = re.sub(r'<img\b[^>]*>', patch_img_tag, raw, flags=re.I)

    # Remove previously appended fallback script block if present
    updated = re.sub(
        r'\n<!-- wp:html -->\s*<script>\s*\(function \(\) \{[\s\S]*?forceLoadLazyImages[\s\S]*?</script>\s*<!-- /wp:html -->\s*\n?',
        '\n',
        updated,
        flags=re.I,
    )

    if updated == raw:
        print(f'page {pid}: no changes needed')
        continue

    payload = json.dumps({'content': updated}).encode()
    req2 = urllib.request.Request(f'{base}/pages/{pid}', data=payload, headers=headers, method='POST')
    with urllib.request.urlopen(req2, timeout=25) as r2:
        res = json.loads(r2.read())

    img_count_after = len(re.findall(r'<img\b[^>]*>', updated, flags=re.I))
    print(f'page {pid}: updated ({res.get("status")}), imgs {img_count_before}->{img_count_after}')

print('done')
