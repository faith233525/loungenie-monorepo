import base64
import re
from pathlib import Path
import requests

BASE = "https://loungenie.com/staging/wp-json/wp/v2"
AUTH = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
HEADERS = {"Authorization": f"Basic {AUTH}", "Content-Type": "application/json"}

ROOT = "https://loungenie.com/staging"
UP = "https://loungenie.com/staging/wp-content/uploads/2026/03/"

pages = [
    (4701, "home", "page-templates/home.html"),
    (2989, "features", "page-templates/features.html"),
    (4862, "about", "page-templates/about.html"),
    (5139, "contact", "page-templates/contact.html"),
    (5285, "videos", "page-templates/videos.html"),
    (5223, "gallery", "page-templates/gallery.html"),
]

hero_map = {
    "home": {
        "img": UP + "lg-home-hero-the-grove-7-scaled.jpg",
        "title": "Transform Premium Seating",
        "copy": "Upgrade poolside guest experience and revenue with one smart cabana product.",
    },
    "features": {
        "img": UP + "IMG_3241-scaled-1.jpg",
        "title": "Product Features",
        "copy": "Every capability is designed for real hospitality operations.",
    },
    "about": {
        "img": UP + "lg-about-westin-hilton-head-scaled.jpg",
        "title": "About LounGenie",
        "copy": "Built around how premium guests and property teams actually operate poolside.",
    },
    "contact": {
        "img": UP + "lg-contact-owc-cabana-scaled.jpg",
        "title": "Contact LounGenie",
        "copy": "Share your property goals and we will map the right product configuration.",
    },
    "videos": {
        "img": UP + "Sea-World-San-Diego.jpg",
        "title": "LounGenie Videos",
        "copy": "Watch product demos and real-world deployment footage.",
    },
    "gallery": {
        "img": UP + "The-Grove-6.jpg",
        "title": "Cabana Installation Photos",
        "copy": "Browse real installations and update media directly from Gutenberg.",
    },
}


def build_cover(img: str, title: str, copy: str) -> str:
    return f'''<!-- wp:cover {{"url":"{img}","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.5,"y":0.5}},"isDark":true,"className":"lg9-page-hero","style":{{"spacing":{{"padding":{{"top":"84px","bottom":"84px","left":"24px","right":"24px"}}}}}} -->
<div class="wp-block-cover is-dark lg9-page-hero" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="{title}" src="{img}" style="object-position:50% 50%" data-object-fit="cover" data-object-position="50% 50%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1080px"}}}} -->
<div class="wp-block-group"><!-- wp:heading {{"level":1,"style":{{"typography":{{"fontSize":"56px","lineHeight":"1.02"}}}}}} -->
<h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">{title}</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {{"style":{{"typography":{{"fontSize":"20px","lineHeight":"1.8"}}}}}} -->
<p style="font-size:20px;line-height:1.8">{copy}</p>
<!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{ROOT}/index.php/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->\n\n'''


for pid, slug, tpl_path in pages:
    r = requests.get(f"{BASE}/pages/{pid}?context=edit", headers=HEADERS, timeout=30)
    j = r.json()
    content = j.get("content", {}).get("raw", "")

    hero = hero_map[slug]
    cover = build_cover(hero["img"], hero["title"], hero["copy"])

    if "<!-- wp:cover" in content:
        content = re.sub(r"<!-- wp:cover[\s\S]*?<!-- /wp:cover -->\s*", cover, content, count=1)
        action = "replaced_cover"
    else:
        content = cover + content
        action = "prepended_cover"

    u = requests.post(
        f"{BASE}/pages/{pid}",
        headers=HEADERS,
        json={"content": content},
        timeout=40,
    )
    print(slug, action, u.status_code)

    Path(tpl_path).write_text(content, encoding="utf-8")
