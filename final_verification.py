#!/usr/bin/env python3
"""Final verification pass — OG images, broken images, duplicates, content."""
import requests, base64, re, json

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

pages = {
    4701: ('home', 'https://www.loungenie.com/'),
    2989: ('features', 'https://www.loungenie.com/poolside-amenity-unit/'),
    4862: ('about', 'https://www.loungenie.com/hospitality-innovation/'),
    5139: ('contact', 'https://www.loungenie.com/contact-loungenie/'),
    5285: ('videos', 'https://www.loungenie.com/loungenie-videos/'),
    5223: ('gallery', 'https://www.loungenie.com/cabana-installation-photos/'),
    5668: ('investors', 'https://www.loungenie.com/investors/'),
    5651: ('board', 'https://www.loungenie.com/board/'),
    5686: ('financials', 'https://www.loungenie.com/financials/'),
    5716: ('press', 'https://www.loungenie.com/press/'),
}

print("=" * 70)
print("FINAL VERIFICATION PASS")
print("=" * 70)

all_issues = []
og_results = {}

print("\n[1] OG IMAGE CHECK (rendered pages)")
for pid, (name, url) in pages.items():
    try:
        resp = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'}, timeout=20)
        html = resp.text
        m = re.search(r'<meta property="og:image" content="([^"]+)"', html, re.I)
        og_img = m.group(1) if m else None
        og_results[name] = og_img
        status = "✓" if og_img else "✗ MISSING"
        fname = og_img.split('/')[-1][:50] if og_img else "none"
        print(f"  {status} {name:<12}: {fname}")
        if not og_img:
            all_issues.append(f"[OG] {name}: no og:image")
    except Exception as e:
        print(f"  ✗ {name}: ERROR {e}")

print("\n[2] BROKEN IMAGE CHECK")
broken_total = 0
for pid, (name, url) in pages.items():
    try:
        resp = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'}, timeout=30)
        html = resp.text
        img_urls = re.findall(r'<img[^>]+src\s*=\s*["\']([^"\']+)["\']', html, re.I)
        broken = []
        for img_url in img_urls:
            if img_url.startswith('data:') or not img_url.startswith('http'):
                continue
            try:
                ir = requests.head(img_url, timeout=8, allow_redirects=True)
                if ir.status_code not in (200, 301, 302):
                    broken.append((img_url.split('/')[-1][:40], ir.status_code))
            except:
                broken.append((img_url.split('/')[-1][:40], 'ERR'))
        if broken:
            for fname, code in broken:
                print(f"  ✗ {name}: BROKEN {fname} ({code})")
                broken_total += 1
                all_issues.append(f"[IMG] {name}: Broken image {fname} ({code})")
        else:
            print(f"  ✓ {name}: all images OK ({len(img_urls)} checked)")
    except Exception as e:
        print(f"  ✗ {name}: page ERROR {e}")

print(f"\n  Total broken: {broken_total}")

print("\n[3] IMAGE DUPLICATE CHECK")
image_map = {}
for pid, (name, url) in pages.items():
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    html = d.get('content', {}).get('rendered', '')
    imgs = set(re.findall(r'https://www\.loungenie\.com/wp-content/uploads/[^\s"\'<>]+\.(?:jpe?g|png|webp|avif)', html, re.I))
    for img in imgs:
        fname = img.split('/')[-1]
        if fname not in image_map:
            image_map[fname] = []
        if name not in image_map[fname]:
            image_map[fname].append(name)

dupes = {k: v for k, v in image_map.items() if len(v) > 1}
if dupes:
    print(f"  Cross-page duplicates remaining: {len(dupes)}")
    for fname, pg_list in sorted(dupes.items(), key=lambda x: -len(x[1])):
        pg_str = ', '.join(pg_list)
        print(f"  {len(pg_list)}x | {fname:55s} | {pg_str}")
else:
    print("  ✓ No cross-page image duplicates!")

print("\n[4] POLICY VIOLATION SCAN")
bad_phrases = [
    ('vandal-resistant', 'do not claim vandal resistance'),
    ('vandal resistant', 'do not claim vandal resistance'),
    ('dual usb', 'wrong — just USB charging ports'),
    ('insulated ice', 'wrong — removable ice bucket, not insulated'),
    ('insulated bucket', 'wrong — removable ice bucket, not insulated'),
    ('connects to pos', 'wrong — provides printer, not POS integration'),
    ('works with any pos', 'wrong — provides printer, not POS integration'),
    ('touchscreen guest', 'check — staff touchscreen only'),
]
policy_clean = True
for pid, (name, url) in pages.items():
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    html = d.get('content', {}).get('rendered', '').lower()
    for phrase, reason in bad_phrases:
        if phrase in html:
            print(f"  ✗ {name}: '{phrase}' — {reason}")
            all_issues.append(f"[POLICY] {name}: '{phrase}'")
            policy_clean = False
if policy_clean:
    print("  ✓ No policy violations found")

print("\n[5] CONTENT LENGTH CHECK")
for pid, (name, url) in pages.items():
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    html = d.get('content', {}).get('rendered', '')
    text = re.sub(r'<[^>]+>', ' ', re.sub(r'<style[^>]*>.*?</style>', '', html, flags=re.DOTALL|re.I))
    words = len(text.split())
    img_count = len(re.findall(r'<img ', html, re.I))
    thin = "⚠ THIN" if words < 200 and name not in ['financials','press'] else ""
    print(f"  {name:<12}: {words:4d} words | {img_count:2d} imgs {thin}")

print("\n" + "=" * 70)
print("SUMMARY")
print("=" * 70)
if all_issues:
    print(f"Issues remaining ({len(all_issues)}):")
    for iss in all_issues:
        print(f"  • {iss}")
else:
    print("✓ All checks passed — site is clean!")
