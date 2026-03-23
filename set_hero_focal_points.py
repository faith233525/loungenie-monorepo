import base64
import re
from pathlib import Path

import requests

BASE = "https://loungenie.com/staging/wp-json/wp/v2"
AUTH = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json"}

PAGES = [
    (4701, "home", "page-templates/home.html", 0.38, 0.52),
    (2989, "features", "page-templates/features.html", 0.50, 0.46),
    (4862, "about", "page-templates/about.html", 0.50, 0.34),
    (5139, "contact", "page-templates/contact.html", 0.52, 0.30),
    (5285, "videos", "page-templates/videos.html", 0.52, 0.44),
    (5223, "gallery", "page-templates/gallery.html", 0.50, 0.40),
]


def pct(v: float) -> str:
    return f"{int(round(v * 100))}%"


for page_id, slug, template_path, fx, fy in PAGES:
    r = requests.get(f"{BASE}/pages/{page_id}?context=edit", headers=HEADERS, timeout=30)
    r.raise_for_status()
    data = r.json()
    content = data.get("content", {}).get("raw", "")

    # Locate first cover block open comment (with or without attrs)
    m = re.search(r"<!-- wp:cover(?:\s+\{[^>]*\})?\s*-->", content)
    if not m:
        print(slug, "no_cover_found")
        continue

    # Pull current hero image URL from first cover image tag
    img_match = re.search(
        r'<img class="wp-block-cover__image-background"[^>]*src="([^"]+)"',
        content,
        flags=re.S,
    )
    img_url = img_match.group(1) if img_match else ""

    open_block_new = (
        f'<!-- wp:cover {{"url":"{img_url}","dimRatio":52,"overlayColor":"black",'
        f'"isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":{fx:.2f},"y":{fy:.2f}}},'
        f'"isDark":true,"className":"lg9-page-hero"}} -->'
    )
    content = content[:m.start()] + open_block_new + content[m.end():]

    # Update first cover background image object position in markup
    x_pct = pct(fx)
    y_pct = pct(fy)
    obj = f"{x_pct} {y_pct}"
    # Ensure first cover image contains style + object position metadata
    content = re.sub(
        r'(<img class="wp-block-cover__image-background"[^>]*?)\sstyle="[^"]*"',
        r'\1 style="object-position:' + obj + '"',
        content,
        count=1,
        flags=re.S,
    )
    if 'data-object-position="' + obj + '"' in content:
        pass
    elif 'data-object-position="' in content:
        content = re.sub(
            r'data-object-position="[^"]*"',
            'data-object-position="' + obj + '"',
            content,
            count=1,
        )
    else:
        content = content.replace(
            'data-object-fit="cover"',
            'data-object-fit="cover" data-object-position="' + obj + '"',
            1,
        )

    u = requests.post(
        f"{BASE}/pages/{page_id}",
        headers=HEADERS,
        json={"content": content},
        timeout=40,
    )
    print(slug, u.status_code, f"focal=({fx:.2f},{fy:.2f})")

    Path(template_path).write_text(content, encoding="utf-8")
