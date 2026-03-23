import urllib.request, json, base64, re

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred}
base = "https://www.loungenie.com/wp-json/wp/v2"

# All pages to audit (id, label, url)
pages = [
    (None, "home",       "https://www.loungenie.com/"),
    (None, "features",   "https://www.loungenie.com/poolside-amenity-unit/"),
    (None, "about",      "https://www.loungenie.com/hospitality-innovation/"),
    (None, "contact",    "https://www.loungenie.com/contact-loungenie/"),
    (None, "videos",     "https://www.loungenie.com/loungenie-videos/"),
    (None, "gallery",    "https://www.loungenie.com/cabana-installation-photos/"),
    (5668, "investors",  "https://www.loungenie.com/investors/"),
    (5651, "board",      "https://www.loungenie.com/board/"),
    (5686, "financials", "https://www.loungenie.com/financials/"),
    (5716, "press",      "https://www.loungenie.com/press/"),
]

results = {}

for page_id, label, url in pages:
    print(f"\nFetching {label}...")
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        html = urllib.request.urlopen(req, timeout=20).read().decode("utf-8", errors="replace")
    except Exception as e:
        print(f"  ERROR: {e}")
        continue

    # Find all <img> tags
    imgs = re.findall(r'<img[^>]+>', html, re.IGNORECASE)
    page_imgs = []
    for img in imgs:
        src = re.search(r'\bsrc=["\']([^"\']+)["\']', img)
        data_src = re.search(r'\bdata-src=["\']([^"\']+)["\']', img)
        alt = re.search(r'\balt=["\']([^"\']*)["\']', img)
        width = re.search(r'\bwidth=["\']([^"\']+)["\']', img)
        height = re.search(r'\bheight=["\']([^"\']+)["\']', img)
        srcset = re.search(r'\bsrcset=["\']([^"\']+)["\']', img)

        actual_src = (data_src.group(1) if data_src else (src.group(1) if src else None))
        if not actual_src or "data:image" in actual_src:
            actual_src = data_src.group(1) if data_src else actual_src

        if not actual_src or actual_src.startswith("data:"):
            continue  # skip placeholders

        # Skip tiny icons / admin assets
        if any(skip in actual_src for skip in ["/wp-admin/", "/wp-includes/", "gravatar.com", "dashicons"]):
            continue

        alt_text = alt.group(1) if alt else ""
        w = width.group(1) if width else ""
        h = height.group(1) if height else ""

        # Detect thumbnail patterns (e.g. -300x200, -150x150, -238x300)
        thumb_match = re.search(r'-(\d+)x(\d+)\.(webp|jpg|jpeg|png)$', actual_src, re.IGNORECASE)
        is_thumb = bool(thumb_match)
        thumb_size = f"{thumb_match.group(1)}x{thumb_match.group(2)}" if thumb_match else ""

        page_imgs.append({
            "src": actual_src,
            "alt": alt_text,
            "w": w,
            "h": h,
            "is_thumb": is_thumb,
            "thumb_size": thumb_size,
        })

    results[label] = page_imgs
    print(f"  Found {len(page_imgs)} images")

# ── Print full report ─────────────────────────────────────────────────────────
print("\n" + "="*80)
print("FULL IMAGE AUDIT REPORT")
print("="*80)

all_issues = []
for label, imgs in results.items():
    if not imgs:
        continue
    print(f"\n{'─'*60}")
    print(f"PAGE: {label.upper()} ({len(imgs)} images)")
    print(f"{'─'*60}")
    for i, img in enumerate(imgs, 1):
        issues = []
        if img["is_thumb"]:
            issues.append(f"THUMBNAIL ({img['thumb_size']})")
        if not img["alt"]:
            issues.append("NO ALT TEXT")
        if "/loungenie.com/loungenie/" in img["src"]:
            issues.append("STAGING URL")

        flag = " ⚠️  [" + ", ".join(issues) + "]" if issues else " ✅"
        print(f"  {i:2}. {img['src'][-80:]}")
        print(f"      alt='{img['alt']}' | w={img['w']} h={img['h']}{flag}")
        if issues:
            all_issues.append((label, img["src"], issues))

print("\n" + "="*80)
print(f"SUMMARY: {sum(len(v) for v in results.values())} total images across {len(results)} pages")
print(f"Issues found: {len(all_issues)}")
print("="*80)
thumbs  = [(l, s, i) for l, s, i in all_issues if any("THUMBNAIL" in x for x in i)]
no_alt  = [(l, s, i) for l, s, i in all_issues if any("NO ALT" in x for x in i)]
staging = [(l, s, i) for l, s, i in all_issues if any("STAGING" in x for x in i)]
print(f"  Thumbnails used where full-size needed: {len(thumbs)}")
print(f"  Missing alt text: {len(no_alt)}")
print(f"  Staging-domain URLs: {len(staging)}")
for label, src, issues in thumbs:
    print(f"    [{label}] {src.split('/')[-1]} → {[x for x in issues if 'THUMBNAIL' in x][0]}")
