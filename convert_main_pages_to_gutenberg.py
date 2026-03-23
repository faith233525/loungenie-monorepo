import base64
import requests

BASE = "https://loungenie.com/staging/wp-json/wp/v2"
AUTH = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
HEADERS = {
    "Authorization": f"Basic {AUTH}",
    "Content-Type": "application/json",
}

ROOT = "https://loungenie.com/staging"
UP = "https://loungenie.com/staging/wp-content/uploads/2026/03/"
UP25 = "https://loungenie.com/staging/wp-content/uploads/2025/10/"

TOKENS = {
    "ROOT": ROOT,
    "hero": UP + "lg-home-hero-the-grove-7-scaled.jpg",
    "hero2": UP + "lg-contact-owc-cabana-scaled.jpg",
    "hero4": UP + "lg-about-westin-hilton-head-scaled.jpg",
    "grove": UP + "The-Grove-6.jpg",
    "sea": UP + "Sea-World-San-Diego.jpg",
    "park1": UP + "IMG_3241-scaled-1.jpg",
    "park2": UP + "IMG_3239-scaled-1.jpg",
    "park3": UP + "IMG_3235-scaled-1.jpg",
    "park4": UP + "IMG_3233-scaled-1.jpg",
    "gallery_water1": UP + "lg-gallery-water-world-cabana-1.jpg",
    "gallery_water2": UP + "lg-gallery-cowabunga-cabana-1-scaled.jpg",
    "gallery_water3": UP + "IMG_2089.jpeg",
    "gallery_water4": UP + "lg-gallery-soaky-10-scaled.jpg",
    "gallery_detail1": UP + "a5ea38b9-4578-4356-a118-f168caa0ec90.jpg",
    "gallery_detail2": UP + "38f4fc95-7925-4625-b0e8-5ba78771c037.jpg",
    "gallery_detail3": UP + "IMG_2079.jpeg",
    "carnival": ROOT + "/wp-content/uploads/2026/02/Carnival-Cruise-Emblem-1-scaled.webp",
    "holiday": ROOT + "/wp-content/uploads/2026/03/logo-holiday-inn.webp",
    "splash": ROOT + "/wp-content/uploads/2026/03/logo-splash-kingdom.webp",
    "hyatt": UP25 + "R-1-scaled.png",
    "pyek": UP25 + "logos-pc-black-horizontal.png",
    "palace": UP25 + "logo-color.png",
    "atlantis": UP25 + "Picture4-300x68.png.webp",
    "cowabunga": UP25 + "cowabunga-vegas-logo-300x173.png.webp",
}


def fill(template: str) -> str:
    out = template
    for key, value in TOKENS.items():
        out = out.replace(f"[[{key}]]", value)
    return out


HOME = """<!-- wp:cover {"url":"[[hero]]","dimRatio":50,"minHeight":620,"isDark":true,"style":{"spacing":{"padding":{"top":"96px","bottom":"96px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover is-dark" style="padding-top:96px;padding-right:24px;padding-bottom:96px;padding-left:24px;min-height:620px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background" alt="Poolside LounGenie hero" src="[[hero]]" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"1100px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontWeight":"700"},"color":{"text":"#9fe0ff"}}} -->
<p class="has-text-color" style="color:#9fe0ff;font-weight:700;letter-spacing:2px;text-transform:uppercase">Smart Cabana Product</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Turn premium seating into a stronger guest experience and higher-yield revenue zone.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>LounGenie combines ordering, storage, charging, and comfort in one commercial poolside unit designed for resorts, hotels, and waterparks.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/index.php/contact-loungenie/">Request a Demo</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/index.php/poolside-amenity-unit/">Explore Features</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:56px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Selected Property Logos</h2>
<!-- /wp:heading -->

<!-- wp:gallery {"linkTo":"none","columns":4,"imageCrop":false} -->
<figure class="wp-block-gallery has-nested-images columns-4 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[carnival]]" alt="Carnival"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[holiday]]" alt="Holiday Inn"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[splash]]" alt="Splash Kingdom"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hyatt]]" alt="Hyatt"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[pyek]]" alt="Park"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[palace]]" alt="Palace Entertainment"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[atlantis]]" alt="Atlantis"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[cowabunga]]" alt="Cowabunga"/></figure><!-- /wp:image --></figure>
<!-- /wp:gallery --></div>
<!-- /wp:group -->"""

FEATURES = """<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph --><p>Product Features</p><!-- /wp:paragraph --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Every feature is built for real poolside operations.</h1><!-- /wp:heading -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[park1]]" alt="LounGenie at cabana"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:list --><ul><li>ORDER: guest QR ordering workflow</li><li>STASH: waterproof safe with waterproof keypad</li><li>CHARGE: solar USB charging ports</li><li>CHILL: removable ice bucket</li><li>Service call button alerts staff touchscreen monitor</li></ul><!-- /wp:list --></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[park2]]" alt="Feature image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park3]]" alt="Feature image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park4]]" alt="Feature image 3"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div>
<!-- /wp:group -->"""

ABOUT = """<!-- wp:cover {"url":"[[hero4]]","dimRatio":45,"minHeight":500,"isDark":true,"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover is-dark" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background" alt="About hero" src="[[hero4]]" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"1080px"}} --><div class="wp-block-group"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Built for how premium guests actually behave poolside.</h1><!-- /wp:heading --><!-- wp:paragraph --><p>LounGenie helps operators upgrade service quality and monetization without adding complexity for teams on deck.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"70px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} --><div class="wp-block-group" style="padding-top:56px;padding-right:24px;padding-bottom:70px;padding-left:24px"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[sea]]" alt="SeaWorld deployment"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:list --><ul><li>No upfront hardware cost model</li><li>PoolSafe handles installation and support</li><li>Built for high-traffic hospitality environments</li><li>Branding options to match property aesthetic</li></ul><!-- /wp:list --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->"""

CONTACT = """<!-- wp:cover {"url":"[[hero2]]","dimRatio":55,"minHeight":420,"isDark":true,"style":{"spacing":{"padding":{"top":"70px","bottom":"70px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover is-dark" style="padding-top:70px;padding-right:24px;padding-bottom:70px;padding-left:24px;min-height:420px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background" alt="Contact hero" src="[[hero2]]" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} --><div class="wp-block-group"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Contact LounGenie</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Tell us about your property and we will map the Product to your operating goals.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"52px","bottom":"70px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"960px"}} --><div class="wp-block-group" style="padding-top:52px;padding-right:24px;padding-bottom:70px;padding-left:24px"><!-- wp:paragraph --><p><strong>Email:</strong> <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><strong>Phone:</strong> <a href="tel:+14166302444">+1 (416) 630-2444</a></p><!-- /wp:paragraph --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="LounGenie cabana setup"/></figure><!-- /wp:image --></div><!-- /wp:group -->"""

VIDEOS = """<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} --><div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">LounGenie Videos</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Replace these blocks with your preferred video embeds or local uploads anytime.</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[sea]]" alt="Video placeholder image"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Resort video placeholder"/></figure><!-- /wp:image --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->"""

GALLERY = """<!-- wp:group {"style":{"spacing":{"padding":{"top":"64px","bottom":"64px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1140px"}} --><div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:64px;padding-left:24px"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Cabana Installation Photos</h1><!-- /wp:heading --><!-- wp:paragraph --><p>All images below are native Gutenberg image blocks. You can replace, reorder, crop, and caption them directly in the editor.</p><!-- /wp:paragraph --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_water1]]" alt="Gallery image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_water2]]" alt="Gallery image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_water3]]" alt="Gallery image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_water4]]" alt="Gallery image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_detail1]]" alt="Gallery image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_detail2]]" alt="Gallery image 6"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[gallery_detail3]]" alt="Gallery image 7"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Gallery image 8"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[sea]]" alt="Gallery image 9"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div><!-- /wp:group -->"""

PAGES = {
    4701: HOME,
    2989: FEATURES,
    4862: ABOUT,
    5139: CONTACT,
    5285: VIDEOS,
    5223: GALLERY,
}

for page_id, template in PAGES.items():
    content = fill(template)
    r = requests.post(
        f"{BASE}/pages/{page_id}",
        headers=HEADERS,
        json={"content": content},
        timeout=40,
    )
    print(page_id, r.status_code)
