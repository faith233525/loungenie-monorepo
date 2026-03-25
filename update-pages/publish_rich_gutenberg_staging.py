#!/usr/bin/env python3

import base64
import requests


STAGING = "https://loungenie.com/staging/wp-json/wp/v2"
AUTH = {
    "Authorization": "Basic " + base64.b64encode("Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2".encode()).decode()
}}

ROOT = "https://loungenie.com/staging"
UP = ROOT + "/wp-content/uploads/2026/03/"
UP25 = ROOT + "/wp-content/uploads/2025/10/"

TOKENS = {
    "ROOT": ROOT,
    # HERO IMAGES (prefer WebP/AVIF)
    "hero": ROOT + "/wp-content/uploads/2026/03/The-Grove-7-scaled.webp",  # was jpg
    "hero2": ROOT + "/wp-content/uploads/2026/03/lg-contact-owc-cabana-scaled.webp",  # was jpg
    "hero3": ROOT + "/wp-content/uploads/2026/03/lg-home-daybed-hilton-scaled.webp",  # was jpg
    "hero4": ROOT + "/wp-content/uploads/2026/03/175-Westin__hhi_bjp_-_low_res.avif",  # AVIF Westin hero

    # GALLERY/DEPLOYMENT IMAGES (prefer WebP/AVIF)
    "grove": ROOT + "/wp-content/uploads/2026/03/The-Grove-6.webp",  # was jpg
    "grove2": ROOT + "/wp-content/uploads/2026/03/The-Grove-2.webp",  # was jpg
    "sea": ROOT + "/wp-content/uploads/2026/03/Sea-World-San-Diego-Edited.webp",  # was jpg
    "lifestyle1": ROOT + "/wp-content/uploads/2026/03/Hilton-waikoloa-Village-2018-10-Kona-Pool-Cabanas-4-scaled.webp",  # was jpg
    "lifestyle2": ROOT + "/wp-content/uploads/2026/03/Sea-World-San-Diego-Edited.webp",  # was jpg
    "lifestyle3": ROOT + "/wp-content/uploads/2026/03/IMG_2078.webp",  # fallback if available
    "water1": ROOT + "/wp-content/uploads/2026/03/lg-gallery-water-world-cabana-1.webp",  # was jpg
    "water2": ROOT + "/wp-content/uploads/2026/03/lg-gallery-cowabunga-cabana-1-scaled.webp",  # already webp
    "water3": ROOT + "/wp-content/uploads/2026/03/IMG_2089.webp",  # fallback if available
    "water4": ROOT + "/wp-content/uploads/2026/03/lg-gallery-soaky-10-scaled.webp",  # was jpg
    "water5": ROOT + "/wp-content/uploads/2026/03/IMG_2090.webp",  # fallback if available
    "water6": ROOT + "/wp-content/uploads/2026/03/IMG_2080.webp",  # fallback if available
    "water7": ROOT + "/wp-content/uploads/2026/03/IMG_2081.webp",  # fallback if available
    # DETAIL IMAGES
    "detail1": ROOT + "/wp-content/uploads/2026/03/a5ea38b9-4578-4356-a118-f168caa0ec90.webp",  # was jpg
    "detail2": ROOT + "/wp-content/uploads/2026/03/38f4fc95-7925-4625-b0e8-5ba78771c037.webp",  # was jpg
    "detail3": ROOT + "/wp-content/uploads/2026/03/IMG_2079.webp",  # fallback if available
    # PARK IMAGES
    "park1": ROOT + "/wp-content/uploads/2026/03/IMG_3241-scaled-1.webp",  # was jpg
    "park2": ROOT + "/wp-content/uploads/2026/03/IMG_3239-scaled-1.webp",  # was jpg
    "park3": ROOT + "/wp-content/uploads/2026/03/IMG_3235-scaled-1.webp",  # was jpg
    "park4": ROOT + "/wp-content/uploads/2026/03/IMG_3233-scaled-1.webp",  # was jpg
    # LOGO STRIP (already optimized)
    "carnival": ROOT + "/wp-content/uploads/2026/02/Carnival-Cruise-Emblem-1-scaled.webp",
    "holiday": ROOT + "/wp-content/uploads/2026/03/logo-holiday-inn.webp",
    "splash": ROOT + "/wp-content/uploads/2026/03/logo-splash-kingdom.webp",
    "hyatt": UP25 + "R-1-scaled.png",  # PNG, no webp found
    "palace": UP25 + "logo-color.png",  # PNG, no webp found
    "cowabunga": UP25 + "cowabunga-vegas-logo-300x173.png.webp",
    "atlantis": UP25 + "Picture4-300x68.png.webp",
}


def fill(template):
    for key, value in TOKENS.items():
        template = template.replace(f"[[{key}]]", value)
    return template


MOBILE_PREMIUM_STYLE = """<!-- wp:html -->
<style>
@media (max-width: 782px) {
    .wp-block-cover {
        min-height: 460px !important;
        padding-top: 72px !important;
        padding-bottom: 72px !important;
    }
    .wp-block-cover h1 {
        font-size: clamp(34px, 9.5vw, 48px) !important;
        line-height: 1.08 !important;
    }
    .wp-block-cover p {
        font-size: clamp(17px, 4.8vw, 20px) !important;
        line-height: 1.6 !important;
    }
    .wp-block-group,
    .wp-block-media-text,
    .wp-block-cover {
        padding-left: 18px !important;
        padding-right: 18px !important;
    }
    .wp-block-columns {
        gap: 14px !important;
    }
    .wp-block-buttons {
        display: grid !important;
        gap: 10px !important;
    }
    .wp-block-button {
        width: 100% !important;
    }
    .wp-block-button__link {
        width: 100% !important;
        text-align: center !important;
    }
    .wp-block-gallery.has-nested-images {
        gap: 10px !important;
    }
}

.lg-logo-slider {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #e8ecef;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafb 100%);
    padding: 14px 0;
}

.lg-logo-track {
    display: flex;
    align-items: center;
    gap: 34px;
    width: max-content;
    animation: lgLogoScroll 26s linear infinite;
    will-change: transform;
}

.lg-logo-track img {
    height: 34px;
    width: auto;
    opacity: 0.95;
    filter: saturate(0.9);
}

.lg-elevated-card {
    border: 1px solid #e5eaee;
    border-radius: 14px;
    box-shadow: 0 12px 26px rgba(11, 28, 44, 0.06);
}

.lg-video-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.lg-video-card {
    background: #ffffff;
    border: 1px solid #e7ebef;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(14, 28, 40, 0.05);
}

.lg-video-thumb {
    position: relative;
    display: block;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: #0f1720;
}

.lg-video-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.lg-video-thumb:hover img {
    transform: scale(1.03);
}

.lg-video-play {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 64px;
    height: 64px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.62);
    color: #ffffff;
    font-size: 26px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.lg-video-body {
    padding: 14px 14px 16px 14px;
}

.lg-video-body h4 {
    margin: 0 0 8px 0;
    font-size: 20px;
    line-height: 1.25;
}

.lg-video-body p {
    margin: 0;
    color: #2c3a46;
}

@keyframes lgLogoScroll {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}

@media (max-width: 782px) {
    .lg-logo-track {
        gap: 22px;
        animation-duration: 20s;
    }
    .lg-logo-track img {
        height: 28px;
    }
    .lg-video-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .lg-video-play {
        width: 56px;
        height: 56px;
        font-size: 22px;
    }
    .lg-video-body h4 {
        font-size: 18px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .lg-logo-track {
        animation: none;
    }
}
</style>
<!-- /wp:html -->
"""


def optimize_markup(content):
        content = MOBILE_PREMIUM_STYLE + content

        # Favor the first hero image for faster first paint.
        content = content.replace(
                '<img class="wp-block-cover__image-background"',
                '<img fetchpriority="high" loading="eager" decoding="async" class="wp-block-cover__image-background"',
                1,
        )

        # Defer non-hero images to reduce mobile payload pressure.
        content = content.replace(
                '<img src="',
                '<img loading="lazy" decoding="async" src="',
        )

        return content


HOME = optimize_markup(fill(
    """<!-- wp:cover {"url":"[[hero]]","dimRatio":55,"overlayColor":"black","isUserOverlayColor":true,"minHeight":640,"focalPoint":{"x":0.42,"y":0.52},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:640px;padding-top:120px;padding-right:24px;padding-bottom:120px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-55 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie resort deployment" src="[[hero]]" style="object-position:42% 52%" data-object-fit="cover" data-object-position="42% 52%"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} -->
<p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Smart premium seating for hospitality</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"64px","lineHeight":"1.02"}}} -->
<h1 class="wp-block-heading" style="font-size:64px;line-height:1.02">Make premium seating feel effortless for guests and more valuable for operators.</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"22px","lineHeight":"1.75"}}} -->
<p style="font-size:22px;line-height:1.75">LounGenie brings ordering, secure storage, charging, and comfort directly into the seat so premium zones can reduce service friction, keep guests in place longer, and run with clearer staff workflows.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Request a Demo</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline","style":{"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/poolside-amenity-unit/">Explore Features</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- wp:list -->
<ul><li>ORDER, STASH, CHARGE, and CHILL integrated at the seat</li><li>QR orders print on a dedicated PoolSafe printer</li><li>Service alerts route separately to a staff monitor</li><li>PoolSafe handles installation and ongoing service support</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"20px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:28px;padding-right:24px;padding-bottom:20px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} -->
<p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Selected property logos</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="lg-logo-slider" aria-label="Selected property and brand logos">
    <div class="lg-logo-track">
        <img src="[[carnival]]" alt="Carnival" />
        <img src="[[holiday]]" alt="Holiday Inn" />
        <img src="[[splash]]" alt="Splash Kingdom" />
        <img src="[[hyatt]]" alt="Hyatt" />
        <img src="[[palace]]" alt="Palace Entertainment" />
        <img src="[[cowabunga]]" alt="Cowabunga" />
        <img src="[[atlantis]]" alt="Atlantis" />
        <img src="[[carnival]]" alt="Carnival" aria-hidden="true" />
        <img src="[[holiday]]" alt="Holiday Inn" aria-hidden="true" />
        <img src="[[splash]]" alt="Splash Kingdom" aria-hidden="true" />
        <img src="[[hyatt]]" alt="Hyatt" aria-hidden="true" />
        <img src="[[palace]]" alt="Palace Entertainment" aria-hidden="true" />
        <img src="[[cowabunga]]" alt="Cowabunga" aria-hidden="true" />
        <img src="[[atlantis]]" alt="Atlantis" aria-hidden="true" />
    </div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1120px"}} -->
<div class="wp-block-group" style="padding-top:56px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} -->
<p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Built for premium seating</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"40px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:40px">Designed for the seat types guests actually book first.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">LounGenie is meant to live inside the highest-value seating on the deck, where convenience, presentation, and service quality need to feel integrated from the first impression onward.</p>
<!-- /wp:paragraph -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"22px","right":"22px"}},"border":{"radius":"10px"},"color":{"background":"#f5f8fb"}}} --><div class="wp-block-group has-background" style="border-radius:10px;background-color:#f5f8fb;padding-top:24px;padding-right:22px;padding-bottom:24px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Cabanas</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Private rental cabanas where premium dwell time depends on guests feeling fully taken care of without leaving the space to chase service, power, or secure storage.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"22px","right":"22px"}},"border":{"radius":"10px"},"color":{"background":"#f5f8fb"}}} --><div class="wp-block-group has-background" style="border-radius:10px;background-color:#f5f8fb;padding-top:24px;padding-right:22px;padding-bottom:24px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Daybeds</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Premium daybed zones that need to feel polished and complete, with the convenience layer built into the seat instead of introduced as visible clutter.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"22px","right":"22px"}},"border":{"radius":"10px"},"color":{"background":"#f5f8fb"}}} --><div class="wp-block-group has-background" style="border-radius:10px;background-color:#f5f8fb;padding-top:24px;padding-right:22px;padding-bottom:24px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Clamshells</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Reserved loungers and covered seating where seat-level ordering, storage, and charging help transform a simple reservation into a more premium-feeling experience.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"24px","left":"22px","right":"22px"}},"border":{"radius":"10px"},"color":{"background":"#f5f8fb"}}} --><div class="wp-block-group has-background" style="border-radius:10px;background-color:#f5f8fb;padding-top:24px;padding-right:22px;padding-bottom:24px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Premium lounge zones</h3><!-- /wp:heading --><!-- wp:paragraph --><p>High-traffic premium seating areas where faster response, cleaner service communication, and a more self-contained guest setup can protect both satisfaction and spend.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"42px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:42px">Why premium seating performs better with LounGenie</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">The product is designed to remove guest friction without making the seating area feel more complicated or less premium.</p>
<!-- /wp:paragraph -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[hero2]]" alt="ORDER feature context"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">ORDER</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Guests scan and order from the seat. Orders print on a dedicated PoolSafe printer so the service flow stays clear.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[park3]]" alt="STASH feature detail"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">STASH</h3><!-- /wp:heading --><!-- wp:paragraph --><p>The waterproof safe and keypad help guests relax and stay longer in high-value seating areas.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="[[grove2]]" alt="CHARGE and CHILL feature context"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">CHARGE + CHILL</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Solar USB charging ports and a removable ice bucket round out the premium seat experience.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">What operators are solving</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Premium seating performs best when common friction points are handled at the seat.</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f7f8f6"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f8f6;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Keeps guests in the premium zone</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Ordering, secure storage, charging, and CHILL are integrated into the seat so guests do not need to leave their reserved area to solve basic needs.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#eef4f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#eef4f7;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Separates service and ordering clearly</h3><!-- /wp:heading --><!-- wp:paragraph --><p>QR orders print on a dedicated PoolSafe printer while general service requests route to a separate staff monitor, reducing mixed signals during peak windows.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f4f1ea"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f4f1ea;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Maintains premium presentation</h3><!-- /wp:heading --><!-- wp:paragraph --><p>The unit is designed to sit naturally within cabanas, daybeds, and reserved lounges without making the seating area feel crowded or improvised.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:media-text {"mediaId":0,"mediaLink":"[[hero4]]","mediaType":"image","imageFill":true,"focalPoint":{"x":0.5,"y":0.36},"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-media-text is-stacked-on-mobile is-image-fill" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px;grid-template-columns:50% auto"><figure class="wp-block-media-text__media" style="background-image:url([[hero4]]);background-position:50% 36%"></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} -->
<p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Guest journey</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">What changes once a guest sits down?</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul><li>Valuables go into a waterproof safe instead of being left exposed.</li><li>Phones remain powered without guests leaving the seating zone.</li><li>Service requests stay organized through a separate staff monitor workflow.</li><li>QR ordering creates a cleaner path from intent to completed order.</li></ul>
<!-- /wp:list -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/cabana-installation-photos/">See more installations</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div></div>
<!-- /wp:media-text -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:56px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Real deployment imagery</h2><!-- /wp:heading --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle1]]" alt="Premium resort deployment"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle2]]" alt="Reserved seating deployment"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle3]]" alt="Cabana lifestyle scene"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Resort cabana row"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hero3]]" alt="Daybed seating with LounGenie"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[sea]]" alt="Water attraction seating zone"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"64px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:64px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Operator FAQ</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Common questions before rollout</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":4} --><h4 class="wp-block-heading">How do orders and service requests stay separate?</h4><!-- /wp:heading --><!-- wp:paragraph --><p>QR orders print on a dedicated PoolSafe printer. General service requests go to a separate staff touchscreen monitor, so teams can manage both flows clearly.</p><!-- /wp:paragraph --><!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Does 2.0 remove the service call button?</h4><!-- /wp:heading --><!-- wp:paragraph --><p>No. 2.0 adds QR ordering on top of Service+, and the service call button remains active for non-order requests.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":4} --><h4 class="wp-block-heading">What is usually the best starting tier?</h4><!-- /wp:heading --><!-- wp:paragraph --><p>Most teams start with the bottleneck they need to solve first: guest convenience at the seat, service workflow clarity, or in-seat order capture.</p><!-- /wp:paragraph --><!-- wp:heading {"level":4} --><h4 class="wp-block-heading">How is rollout handled operationally?</h4><!-- /wp:heading --><!-- wp:paragraph --><p>PoolSafe handles installation, service, and ongoing support. Property teams stay focused on guest operations while rollout is coordinated around seasonal timing.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:cover {"overlayColor":"black","isUserOverlayColor":true,"dimRatio":40,"minHeight":260,"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"24px","right":"24px"}}},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:260px;padding-top:48px;padding-right:24px;padding-bottom:48px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"920px"}} --><div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">PoolSafe installs it, services it, and supports the rollout.</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">The commercial model is designed to reduce adoption friction while keeping the guest experience premium from day one.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Talk with the team</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->"""
))


FEATURES = optimize_markup(fill(
    """<!-- wp:cover {"url":"[[park1]]","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":560,"focalPoint":{"x":0.5,"y":0.46},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:560px;padding-top:96px;padding-right:24px;padding-bottom:96px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie product features" src="[[park1]]" style="object-position:50% 46%" data-object-fit="cover" data-object-position="50% 46%"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Feature overview</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"60px","lineHeight":"1.05"}}} --><h1 class="wp-block-heading" style="font-size:60px;line-height:1.05">Three tiers, one premium standard for poolside service.</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"20px","lineHeight":"1.8"}}} --><p style="font-size:20px;line-height:1.8">LounGenie scales from premium convenience to full ordering support while keeping the guest journey polished and the operational path clear for staff.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Talk through your seating mix</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/cabana-installation-photos/">See installations</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"56px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Tier options</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Choose the level of service support that fits your property.</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">All tiers are cumulative: start with guest convenience at the seat, add structured service signaling, then add QR ordering when your priority shifts to faster in-seat order capture.</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"14px"},"color":{"background":"#f6f8fa"}}} --><div class="wp-block-group has-background" style="border-radius:14px;background-color:#f6f8fa;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:image --><figure class="wp-block-image"><img src="[[park1]]" alt="Classic tier context"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Classic</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Best when your immediate goal is making premium seats feel complete and self-contained for guests.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>Waterproof safe with waterproof keypad</li><li>Solar USB charging ports</li><li>Removable ice bucket</li><li>Commercial-grade aluminum interior</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"14px"},"color":{"background":"#eef4f8"}}} --><div class="wp-block-group has-background" style="border-radius:14px;background-color:#eef4f8;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:image --><figure class="wp-block-image"><img src="[[park2]]" alt="Service plus tier context"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Service+</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Best when your team needs cleaner handling of non-order service requests during busy premium seating periods.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>Everything in Classic</li><li>Service call button</li><li>Alert routed to separate staff service monitor</li><li>Cleaner request handling for busy premium zones</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"14px"},"color":{"background":"#f5f4ef"}}} --><div class="wp-block-group has-background" style="border-radius:14px;background-color:#f5f4ef;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:image --><figure class="wp-block-image"><img src="[[park4]]" alt="2.0 tier context"/></figure><!-- /wp:image --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">2.0</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Best when you are ready for QR ordering at the seat while still keeping service alerts in a separate workflow.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>Everything in Service+</li><li>QR ordering added at the seat</li><li>Orders print on a dedicated PoolSafe printer</li><li>Service call button remains active for general requests</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<div class="wp-block-media-text is-stacked-on-mobile is-image-fill" style="padding-top:64px;padding-right:24px;padding-bottom:64px;padding-left:24px;grid-template-columns:48% auto"><figure class="wp-block-media-text__media" style="background-image:url([[hero2]]);background-position:52% 30%"></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">How ordering works</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">The ordering path is clear by design.</h2><!-- /wp:heading --><!-- wp:list --><ol><li>Guest scans the QR code from the seat.</li><li>The order prints on a dedicated PoolSafe printer.</li><li>Staff fulfills the request through the existing service flow.</li><li>General service requests still route through the separate service monitor.</li></ol><!-- /wp:list --><!-- wp:paragraph --><p>This keeps food and beverage communication separate from service alerts and avoids muddled request handling during peak hours.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Ordering does not depend on POS integration. The dedicated printer path is intentional so properties can adopt in-seat ordering without rebuilding existing point-of-sale infrastructure.</p><!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:56px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Program strengths</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f7f8f6"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f8f6;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Build quality</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Solid aluminum interior</li><li>Corrosion-resistant construction</li><li>Built for high-use hospitality environments</li><li>Clean visual presentation inside premium seating</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f1f5f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f1f5f7;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Customization</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Color-matched to property aesthetic</li><li>Brand logos available on units</li><li>Advertising surfaces available</li><li>Configurable across cabanas, daybeds, and premium lounges</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f6f4ee"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f6f4ee;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Support model</h3><!-- /wp:heading --><!-- wp:list --><ul><li>PoolSafe installs and services the units</li><li>Teams receive rollout support</li><li>The program is built for operational practicality</li><li>Designed to fit premium hospitality pacing</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"56px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Choosing a tier</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">A practical way to decide where to start</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f7f8f6"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f8f6;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Start with Classic when</h3><!-- /wp:heading --><!-- wp:list --><ul><li>You want a cleaner premium guest setup immediately.</li><li>Your first priority is in-seat convenience and presentation.</li><li>You plan to evaluate service-flow upgrades in a later phase.</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#eef4f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#eef4f7;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Choose Service+ when</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Your team needs clearer handling of in-seat service requests.</li><li>Premium areas are busy enough that request flow can get noisy.</li><li>You want better staff visibility before adding seat-level ordering.</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f4f1ea"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f4f1ea;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Move to 2.0 when</h3><!-- /wp:heading --><!-- wp:list --><ul><li>You are ready for QR ordering directly from the seat.</li><li>You want order intent captured without adding guest friction.</li><li>You still need separate service alerts for non-order requests.</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:24px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Closer product views</h2><!-- /wp:heading --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[park2]]" alt="LounGenie detail image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park3]]" alt="LounGenie detail image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park4]]" alt="LounGenie detail image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[detail1]]" alt="LounGenie detail image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[detail2]]" alt="LounGenie detail image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[detail3]]" alt="LounGenie detail image 6"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Discuss your rollout</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->"""
))


ABOUT = optimize_markup(fill(
    """
<!-- wp:html -->
<meta name=\"description\" content=\"About LounGenie: Discover how PoolSafe's premium seating platform transforms guest experience for hotels, resorts, and waterparks. Learn about our mission, team, and hospitality innovation.\" />
<script type=\"application/ld+json\">{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Organization\",\n  \"name\": \"LounGenie by PoolSafe\",\n  \"url\": \"https://www.loungenie.com/\",\n  \"logo\": \"[[hero]]\",\n  \"contactPoint\": [{\n    \"@type\": \"ContactPoint\",\n    \"telephone\": \"+1-416-630-2444\",\n    \"contactType\": \"customer service\",\n    \"email\": \"info@poolsafeinc.com\"\n  }],\n  \"sameAs\": [\n    \"https://www.linkedin.com/company/poolsafe-inc-\",\n    \"https://www.loungenie.com/\"\n  ]\n}\n</script>
<!-- /wp:html -->
<!-- wp:cover {\"url\":\"[[hero4]]\",\"dimRatio\":52,\"overlayColor\":\"black\",\"isUserOverlayColor\":true,\"minHeight\":560,\"focalPoint\":{\"x\":0.5,\"y\":0.36},\"isDark\":true} -->
<div class=\"wp-block-cover is-dark\" style=\"min-height:560px;padding-top:96px;padding-right:24px;padding-bottom:96px;padding-left:24px\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim\"></span><img class=\"wp-block-cover__image-background\" alt=\"About LounGenie premium seating platform for hotels, resorts, and waterparks\" src=\"[[hero4]]\" style=\"object-position:50% 36%\" data-object-fit=\"cover\" data-object-position=\"50% 36%\"/><div class=\"wp-block-cover__inner-container\"><!-- wp:group {\"layout\":{\"type\":\"constrained\",\"contentSize\":\"980px\"}} --><div class=\"wp-block-group\"><!-- wp:paragraph {\"style\":{\"typography\":{\"textTransform\":\"uppercase\",\"letterSpacing\":\"2px\",\"fontSize\":\"12px\"}}} --><p style=\"font-size:12px;letter-spacing:2px;text-transform:uppercase\">About the platform</p><!-- /wp:paragraph --><!-- wp:heading {\"level\":1,\"style\":{\"typography\":{\"fontSize\":\"58px\",\"lineHeight\":\"1.05\"}}} --><h1 class=\"wp-block-heading\" style=\"font-size:58px;line-height:1.05\">About LounGenie: Premium Poolside Seating Innovation</h1><!-- /wp:heading --><!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"20px\",\"lineHeight\":\"1.8\"}}} --><p style=\"font-size:20px;line-height:1.8\">LounGenie by PoolSafe is engineered to help operators modernize premium seating without turning the product into a service burden for the property team. Discover our mission, team, and commitment to hospitality innovation.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"72px\",\"bottom\":\"56px\",\"left\":\"24px\",\"right\":\"24px\"}}},\"layout\":{\"type\":\"constrained\",\"contentSize\":\"1180px\"}} -->
<div class=\"wp-block-group\" style=\"padding-top:72px;padding-right:24px;padding-bottom:56px;padding-left:24px\"><!-- wp:columns --><div class=\"wp-block-columns is-not-stacked-on-mobile\"><!-- wp:column {\"width\":\"48%\"} --><div class=\"wp-block-column\" style=\"flex-basis:48%\"><!-- wp:image --><figure class=\"wp-block-image\"><img src=\"[[hero3]]\" alt=\"Modern daybed environment with LounGenie premium seating\"/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column {\"width\":\"52%\"} --><div class=\"wp-block-column\" style=\"flex-basis:52%\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"26px\",\"bottom\":\"26px\",\"left\":\"26px\",\"right\":\"26px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f5f7f8\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f5f7f8;padding-top:26px;padding-right:26px;padding-bottom:26px;padding-left:26px\"><!-- wp:paragraph {\"style\":{\"typography\":{\"textTransform\":\"uppercase\",\"letterSpacing\":\"2px\",\"fontSize\":\"12px\"}}} --><p style=\"font-size:12px;letter-spacing:2px;text-transform:uppercase\">Built by PoolSafe</p><!-- /wp:paragraph --><!-- wp:heading {\"level\":2} --><h2 class=\"wp-block-heading\">Designed to belong inside premium seating, not beside it.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>LounGenie is designed and built in Canada by PoolSafe for hospitality teams that care about visual quality, reliable operation, and a cleaner seat-level guest journey.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Every feature is meant to earn its place in the cabana. The product has to look right, function clearly, and support the revenue logic of premium seating.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>Commercial-grade construction</li><li>Color and branding flexibility</li><li>Installation and service handled by PoolSafe</li><li>Designed for premium seating environments</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"16px\",\"bottom\":\"56px\",\"left\":\"24px\",\"right\":\"24px\"}}},\"layout\":{\"type\":\"constrained\",\"contentSize\":\"1180px\"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">What makes it different</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">The platform is engineered around visual fit and operational clarity.</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f7f7f3"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f7f3;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Commercial construction</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Solid aluminum interior and corrosion-resistant materials are used to handle demanding outdoor hospitality use.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#eef4f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#eef4f7;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Service-centered workflow</h3><!-- /wp:heading --><!-- wp:paragraph --><p>General service requests route separately from printed QR orders so staff interaction remains easier to manage at busy properties.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f4f1ea"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f4f1ea;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Brand flexibility</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Units can be matched to property colors and support logo or branded-advertising treatments.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"56%"} --><div class="wp-block-column" style="flex-basis:56%"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Why hospitality teams evaluate it now</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Premium seating programs are expected to deliver more than a reserved chair or cabana. Guests expect secure storage, charging, fast service access, and a polished environment that still feels relaxed.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Operators evaluate LounGenie when they want those expectations handled at the seat itself, while preserving a workflow staff can actually manage during peak demand.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>The goal is not to add more complexity to premium zones. The goal is to remove repeated guest and staff friction points while keeping the seating area visually on-brand.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"width":"44%"} --><div class="wp-block-column" style="flex-basis:44%"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f6f8fa"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f6f8fa;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Common evaluation questions</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Does it fit the visual standard of the property?</li><li>Will it reduce common guest friction at the seat?</li><li>Can staff manage ordering and service signals without confusion?</li><li>Can the rollout be timed around the busy season?</li></ul><!-- /wp:list --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:24px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Deployment and presentation</h2><!-- /wp:heading --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle1]]" alt="Resort deployment image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle2]]" alt="Resort deployment image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove2]]" alt="Resort deployment image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[sea]]" alt="Resort deployment image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hero2]]" alt="Resort deployment image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Resort deployment image 6"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">IAAPA Brass Ring Award recognition reflects the product focus on practical innovation and guest-facing hospitality value.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Talk with our team</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->"""
))


CONTACT = optimize_markup(fill(
    """<!-- wp:cover {"url":"[[hero2]]","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":540,"focalPoint":{"x":0.52,"y":0.3},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:540px;padding-top:92px;padding-right:24px;padding-bottom:92px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Contact LounGenie" src="[[hero2]]" style="object-position:52% 30%" data-object-fit="cover" data-object-position="52% 30%"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Contact LounGenie</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"58px","lineHeight":"1.05"}}} --><h1 class="wp-block-heading" style="font-size:58px;line-height:1.05">Start the conversation before peak season.</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"20px","lineHeight":"1.8"}}} --><p style="font-size:20px;line-height:1.8">Tell us about your property and we will map a practical rollout plan around your seating mix, brand goals, and deployment timing.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:info@poolsafeinc.com">Email the team</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/poolside-amenity-unit/">Review features first</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->

<div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:48px;padding-left:24px"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"50%"} --><div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"top":"26px","bottom":"26px","left":"26px","right":"26px"}},"border":{"radius":"14px"},"color":{"background":"#f6f8fa"}}} --><div class="wp-block-group has-background" style="border-radius:14px;background-color:#f6f8fa;padding-top:26px;padding-right:26px;padding-bottom:26px;padding-left:26px"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Reach out today</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong>Email:</strong> <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><strong>Phone:</strong> <a href="tel:+14166302444">+1 (416) 630-2444</a><br><strong>Address:</strong> 906 Magnetic Drive, North York, ON M3J 2C4, Canada</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">What to have ready</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Your property type and premium seating mix (cabana, daybed, clamshell, lounge).</li><li>Your rollout timing and the season you are planning around.</li><li>Your current priority: guest convenience, service workflow clarity, or in-seat order capture.</li><li>Any branding, color, or placement constraints we should account for.</li></ul><!-- /wp:list --><!-- wp:paragraph --><p>If you are unsure which tier fits, that is normal. The first call is designed to map your priorities to Classic, Service+, or 2.0 in a practical sequence.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column {"width":"50%"} --><div class="wp-block-column" style="flex-basis:50%"><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="LounGenie deployment in premium seating zone"/></figure><!-- /wp:image --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Real deployment imagery from an active premium seating environment.</p><!-- /wp:paragraph --><!-- wp:group {"style":{"spacing":{"padding":{"top":"18px","bottom":"18px","left":"18px","right":"18px"}},"border":{"radius":"12px"},"color":{"background":"#f2efe7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f2efe7;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px"><!-- wp:paragraph --><p><strong>Best fit:</strong> cabanas, daybeds, clamshells, and reserved premium seating where guest spend and dwell time matter most.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"40px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:40px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Who usually reaches out</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"border":{"radius":"12px"},"color":{"background":"#f7f8f6"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f8f6;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Resorts and hotels</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Teams looking to improve the value and completeness of reserved cabanas, daybeds, and poolside VIP seating.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"border":{"radius":"12px"},"color":{"background":"#eef4f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#eef4f7;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Waterparks and attractions</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Operators that need premium zones to stay organized and guest-friendly even when surrounding traffic is heavier and faster paced.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"20px","right":"20px"}},"border":{"radius":"12px"},"color":{"background":"#f4f1ea"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f4f1ea;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Owners and development teams</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Groups evaluating how branded premium seating can look more finished at launch or during a broader amenity refresh.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"48px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:48px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">The process</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">A rollout conversation built around real operating constraints.</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f7f8f6"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f7f8f6;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">1. Property review</h3><!-- /wp:heading --><!-- wp:paragraph --><p>We review seat count, venue type, and the operational rhythm of the premium seating area.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#eef4f7"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#eef4f7;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">2. Rollout mapping</h3><!-- /wp:heading --><!-- wp:paragraph --><p>We align tier choice, branding, placement, and deployment timing with your property goals.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}},"border":{"radius":"12px"},"color":{"background":"#f4f1ea"}}} --><div class="wp-block-group has-background" style="border-radius:12px;background-color:#f4f1ea;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">3. Installation and launch</h3><!-- /wp:heading --><!-- wp:paragraph --><p>PoolSafe handles installation and support so the property team can stay focused on operations.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:media-text {"mediaId":0,"mediaLink":"[[lifestyle2]]","mediaType":"image","imageFill":true,"focalPoint":{"x":0.5,"y":0.5}} --><div class="wp-block-media-text is-stacked-on-mobile is-image-fill" style="grid-template-columns:48% auto"><figure class="wp-block-media-text__media" style="background-image:url([[lifestyle2]]);background-position:50% 50%"></figure><div class="wp-block-media-text__content"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">What we plan together</h2><!-- /wp:heading --><!-- wp:list --><ul><li>Seat mix and density across cabanas, daybeds, or premium lounges</li><li>Branding treatments and color matching</li><li>Operational fit between Classic, Service+, and 2.0</li><li>Installation timing before your busiest traffic period</li></ul><!-- /wp:list --><!-- wp:paragraph --><p>The goal is a rollout that looks intentional, feels premium, and makes sense for how your team actually serves guests.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>The commercial model is structured with $0 upfront and a revenue-share approach, so teams can evaluate fit and timing without a heavy initial capital hurdle.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="mailto:info@poolsafeinc.com">Email the team</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/poolside-amenity-unit/">Review features</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div></div><!-- /wp:media-text --></div>
<!-- /wp:group -->"""
))

CONTACT_EXTRA = fill(
    """<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Deployment inspiration</h2><!-- /wp:heading --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Contact gallery image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle1]]" alt="Contact gallery image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park1]]" alt="Contact gallery image 3"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div>
<!-- /wp:group -->"""
)

contact_parts = CONTACT.rsplit("<!-- /wp:group -->", 1)
CONTACT = contact_parts[0] + CONTACT_EXTRA + "<!-- /wp:group -->" + contact_parts[1]


VIDEOS = optimize_markup(fill(
    """<!-- wp:cover {"url":"[[sea]]","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":520,"focalPoint":{"x":0.52,"y":0.44},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:520px;padding-top:92px;padding-right:24px;padding-bottom:92px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie videos" src="[[sea]]" style="object-position:52% 44%" data-object-fit="cover" data-object-position="52% 44%"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Video library</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"58px","lineHeight":"1.05"}}} --><h1 class="wp-block-heading" style="font-size:58px;line-height:1.05">Watch how seat-level ordering and service routing work in real environments.</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"20px","lineHeight":"1.8"}}} --><p style="font-size:20px;line-height:1.8">Use these videos to understand product operation, staff workflow, and visual fit across resort, attraction, and premium seating contexts before planning a rollout.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"64px","bottom":"28px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:28px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p class="has-text-align-center" style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Featured and supporting videos</p><!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Review the platform from product story to deployment context.</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">The library now uses lightweight preview cards for faster load on mobile while preserving full video access.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:html -->
<div class="lg-video-grid" aria-label="LounGenie video library">
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=EZ2CfBU30Ho" target="_blank" rel="noopener noreferrer" aria-label="Watch ORDER, STASH, CHARGE, CHILL overview video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/EZ2CfBU30Ho/hqdefault.jpg" alt="ORDER, STASH, CHARGE, CHILL overview thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>ORDER, STASH, CHARGE, CHILL overview</h4><p>A concise overview of the core product story and premium seat experience.</p></div>
  </article>
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=bdVikQssTFc" target="_blank" rel="noopener noreferrer" aria-label="Watch Smarter guest ordering video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/bdVikQssTFc/hqdefault.jpg" alt="Smarter guest ordering thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>Smarter guest ordering</h4><p>See how ordering can become cleaner and faster from the seat.</p></div>
  </article>
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=Pmvd2P8e1ew" target="_blank" rel="noopener noreferrer" aria-label="Watch Built for multiple seating settings video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/Pmvd2P8e1ew/hqdefault.jpg" alt="Built for multiple seating settings thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>Built for multiple seating settings</h4><p>A broader look at adaptation across premium seating formats.</p></div>
  </article>
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=M48NYM06JgY" target="_blank" rel="noopener noreferrer" aria-label="Watch Premium guest experience context video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/M48NYM06JgY/hqdefault.jpg" alt="Premium guest experience context thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>Premium guest experience context</h4><p>Deployment footage in polished resort-style environments.</p></div>
  </article>
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=PhV1JVo9POI" target="_blank" rel="noopener noreferrer" aria-label="Watch Active deployment story video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/PhV1JVo9POI/hqdefault.jpg" alt="Active deployment story thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>Active deployment story</h4><p>A live hospitality example showing operational fit in use.</p></div>
  </article>
  <article class="lg-video-card">
    <a class="lg-video-thumb" href="https://www.youtube.com/watch?v=3Rjba7pWs_I" target="_blank" rel="noopener noreferrer" aria-label="Watch High-traffic water attraction install video"><img loading="lazy" decoding="async" src="https://i.ytimg.com/vi/3Rjba7pWs_I/hqdefault.jpg" alt="High-traffic water attraction install thumbnail" /><span class="lg-video-play" aria-hidden="true">&#9658;</span></a>
    <div class="lg-video-body"><h4>High-traffic water attraction install</h4><p>An energetic environment where speed, clarity, and premium seating value all matter.</p></div>
  </article>
</div>
<!-- /wp:html -->

<!-- wp:group {"className":"lg-elevated-card","style":{"spacing":{"padding":{"top":"18px","bottom":"18px","left":"20px","right":"20px"},"margin":{"top":"24px"}},"color":{"background":"#f6f8fa"}}} -->
<div class="wp-block-group lg-elevated-card has-background" style="background-color:#f6f8fa;margin-top:24px;padding-top:18px;padding-right:20px;padding-bottom:18px;padding-left:20px"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Start with the overview, then ordering-flow clips, then deployment context. This sequence gives decision-makers a faster and clearer evaluation path.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Request your demo</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->"""
))

GALLERY = optimize_markup(fill(
    """<!-- wp:cover {"url":"[[grove]]","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":540,"focalPoint":{"x":0.5,"y":0.4},"isDark":true} -->
<div class="wp-block-cover is-dark" style="min-height:540px;padding-top:92px;padding-right:24px;padding-bottom:92px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Cabana installation photos" src="[[grove]]" style="object-position:50% 40%" data-object-fit="cover" data-object-position="50% 40%"/><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"980px"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Gallery</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"58px","lineHeight":"1.05"}}} --><h1 class="wp-block-heading" style="font-size:58px;line-height:1.05">See how LounGenie integrates into premium seating layouts across real deployments.</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"20px","lineHeight":"1.8"}}} --><p style="font-size:20px;line-height:1.8">From cabana rows to high-traffic water attractions, these visuals help teams evaluate placement, finish quality, and operational fit before rollout.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"64px","bottom":"40px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:40px;padding-left:24px"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Resort cabanas and daybeds</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Resort cabanas and daybed installations</h2><!-- /wp:heading --><!-- wp:paragraph --><p>These images show how LounGenie reads in premium hospitality seating areas where visual fit matters as much as function.</p><!-- /wp:paragraph --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[hero]]" alt="Resort installation image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hero2]]" alt="Resort installation image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hero3]]" alt="Resort installation image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[hero4]]" alt="Resort installation image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove]]" alt="Resort installation image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[grove2]]" alt="Resort installation image 6"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle1]]" alt="Resort installation image 7"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle2]]" alt="Resort installation image 8"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[lifestyle3]]" alt="Resort installation image 9"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"24px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"980px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">These resort views show how the unit can read as part of the premium furniture plan rather than as add-on technology, while still staying compact enough for denser cabana and daybed layouts.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"24px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"980px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">How teams use this gallery during planning</h3><!-- /wp:heading --><!-- wp:list --><ul><li>Compare how the unit scale reads beside actual cabana and daybed furniture.</li><li>Review placement options relative to guest movement, shade, and service paths.</li><li>Use detail views to confirm finish quality expectations before rollout decisions.</li></ul><!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"40px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:40px;padding-left:24px"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Water attractions and high-volume settings</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Water attraction and high-volume deployment views</h2><!-- /wp:heading --><!-- wp:paragraph --><p>These galleries highlight broader deployment conditions where premium seating has to perform through heavier guest flow.</p><!-- /wp:paragraph --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[water1]]" alt="Water attraction image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water2]]" alt="Water attraction image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water3]]" alt="Water attraction image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water4]]" alt="Water attraction image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water5]]" alt="Water attraction image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water6]]" alt="Water attraction image 6"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water7]]" alt="Water attraction image 7"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park1]]" alt="Water attraction image 8"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park3]]" alt="Water attraction image 9"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"24px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"980px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">In these higher-traffic settings, look for how the unit placement preserves guest circulation and still keeps service interactions visible and manageable for staff.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","bottom":"24px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"980px"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">These higher-volume examples show that the platform can still feel organized and premium even when the surrounding environment is faster, louder, and more operationally demanding.</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Amenity details</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Closer amenity details</h2><!-- /wp:heading --><!-- wp:paragraph --><p>These detail shots help operators judge the fit and finish of the waterproof safe, charging access, and overall seat-level presentation before planning a rollout.</p><!-- /wp:paragraph --><!-- wp:gallery {"linkTo":"none","columns":3,"imageCrop":false} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="[[detail1]]" alt="Amenity detail image 1"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[detail2]]" alt="Amenity detail image 2"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[detail3]]" alt="Amenity detail image 3"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park2]]" alt="Amenity detail image 4"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[park4]]" alt="Amenity detail image 5"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="[[water7]]" alt="Amenity detail image 6"/></figure><!-- /wp:image --></figure><!-- /wp:gallery --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[[ROOT]]/contact-loungenie/">Start the conversation</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->"""
))



BOARD = optimize_markup(fill(
    """
<!-- wp:html -->
<meta name=\"description\" content=\"LounGenie Board of Directors: Meet the leadership team driving hospitality innovation for hotels, resorts, and waterparks. Board governance, CEO, and executive contacts for PoolSafe Inc.\" />
<script type=\"application/ld+json\">{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Organization\",\n  \"name\": \"LounGenie by PoolSafe\",\n  \"url\": \"https://www.loungenie.com/\",\n  \"logo\": \"[[hero]]\",\n  \"contactPoint\": [{\n    \"@type\": \"ContactPoint\",\n    \"telephone\": \"+1-416-630-2444\",\n    \"contactType\": \"customer service\",\n    \"email\": \"info@poolsafeinc.com\"\n  }],\n  \"sameAs\": [\n    \"https://www.linkedin.com/company/poolsafe-inc-\",\n    \"https://www.loungenie.com/\"\n  ]\n}\n</script>
<!-- /wp:html -->
<!-- wp:cover {\"url\":\"[[hero3]]\",\"dimRatio\":52,\"overlayColor\":\"black\",\"isUserOverlayColor\":true,\"minHeight\":520,\"focalPoint\":{\"x\":0.5,\"y\":0.36},\"isDark\":true} -->
<div class=\"wp-block-cover is-dark\" style=\"min-height:520px;padding-top:92px;padding-right:24px;padding-bottom:92px;padding-left:24px\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim\"></span><img class=\"wp-block-cover__image-background\" alt=\"LounGenie Board of Directors and leadership team\" src=\"[[hero3]]\" style=\"object-position:50% 36%\" data-object-fit=\"cover\" data-object-position=\"50% 36%\"/><div class=\"wp-block-cover__inner-container\"><!-- wp:group {\"layout\":{\"type\":\"constrained\",\"contentSize\":\"980px\"}} --><div class=\"wp-block-group\"><!-- wp:heading {\"level\":1,\"style\":{\"typography\":{\"fontSize\":\"54px\",\"lineHeight\":\"1.05\"}}} --><h1 class=\"wp-block-heading\" style=\"font-size:54px;line-height:1.05\">LounGenie Board of Directors & Leadership Team</h1><!-- /wp:heading --><!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"20px\",\"lineHeight\":\"1.8\"}}} --><p style=\"font-size:20px;line-height:1.8\">Meet the LounGenie board and executive team driving hospitality innovation for hotels, resorts, and waterparks. Board governance, CEO, and executive contacts for PoolSafe Inc.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div>
<!-- /wp:cover -->
<!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"56px\",\"bottom\":\"56px\",\"left\":\"24px\",\"right\":\"24px\"}}},\"layout\":{\"type\":\"constrained\",\"contentSize\":\"1180px\"}} --><div class=\"wp-block-group\" style=\"padding-top:56px;padding-right:24px;padding-bottom:56px;padding-left:24px\"><!-- wp:heading {\"level\":2} --><h2 class=\"wp-block-heading\">Meet the LounGenie Leadership Team</h2><!-- /wp:heading --><div class=\"wp-block-columns\"><!-- wp:column --><div class=\"wp-block-column\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"18px\",\"bottom\":\"18px\",\"left\":\"18px\",\"right\":\"18px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f6f8fa\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f6f8fa;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px\"><!-- wp:heading {\"level\":3} --><h3 class=\"wp-block-heading\">David Berger</h3><!-- /wp:heading --><p>CEO</p></div><!-- /wp:group --></div><!-- /wp:column --><div class=\"wp-block-column\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"18px\",\"bottom\":\"18px\",\"left\":\"18px\",\"right\":\"18px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f6f8fa\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f6f8fa;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px\"><!-- wp:heading {\"level\":3} --><h3 class=\"wp-block-heading\">Steven Glaser</h3><!-- /wp:heading --><p>COO, CFO & Director</p></div><!-- /wp:group --></div><!-- /wp:column --><div class=\"wp-block-column\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"18px\",\"bottom\":\"18px\",\"left\":\"18px\",\"right\":\"18px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f6f8fa\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f6f8fa;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px\"><!-- wp:heading {\"level\":3} --><h3 class=\"wp-block-heading\">Steven Mintz</h3><!-- /wp:heading --><p>Director</p></div><!-- /wp:group --></div><!-- /wp:column --><div class=\"wp-block-column\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"18px\",\"bottom\":\"18px\",\"left\":\"18px\",\"right\":\"18px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f6f8fa\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f6f8fa;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px\"><!-- wp:heading {\"level\":3} --><h3 class=\"wp-block-heading\">Gillian Deacon</h3><!-- /wp:heading --><p>Marketing Executive</p></div><!-- /wp:group --></div><!-- /wp:column --><div class=\"wp-block-column\"><!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"18px\",\"bottom\":\"18px\",\"left\":\"18px\",\"right\":\"18px\"}},\"border\":{\"radius\":\"14px\"},\"color\":{\"background\":\"#f6f8fa\"}}} --><div class=\"wp-block-group has-background\" style=\"border-radius:14px;background-color:#f6f8fa;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px\"><!-- wp:heading {\"level\":3} --><h3 class=\"wp-block-heading\">Robert Pratt</h3><!-- /wp:heading --><p>Director</p></div><!-- /wp:group --></div><!-- /wp:column --></div></div><!-- /wp:columns --><p style=\"margin:16px 0 0;\">Source page: <a href=\"https://www.loungenie.com/board/\" target=\"_blank\" rel=\"noopener noreferrer\">www.loungenie.com/board/</a></p></div>
<!-- /wp:group -->
<!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"32px\",\"bottom\":\"56px\",\"left\":\"24px\",\"right\":\"24px\"}}},\"layout\":{\"type\":\"constrained\",\"contentSize\":\"980px\"}} --><div class=\"wp-block-group\" style=\"padding-top:32px;padding-right:24px;padding-bottom:56px;padding-left:24px\"><!-- wp:heading {\"level\":2} --><h2 class=\"wp-block-heading\">Board and Governance Inquiries</h2><!-- /wp:heading --><p>Email: <a href=\"mailto:info@poolsafeinc.com\">info@poolsafeinc.com</a><br>Phone: <a href=\"tel:+14166302444\">+1 (416) 630-2444</a></p></div><!-- /wp:group -->
"""
))

PAGES = [
    (4701, "HOME", HOME),
    (2989, "FEATURES", FEATURES),
    (4862, "ABOUT", ABOUT),
    (5651, "BOARD", BOARD),
    (5139, "CONTACT", CONTACT),
    (5285, "VIDEOS", VIDEOS),
    (5223, "GALLERY", GALLERY),
]


def publish_page(page_id, name, content):
    response = requests.post(
        f"{STAGING}/pages/{page_id}",
        headers=AUTH,
        json={"content": content, "status": "publish"},
        timeout=90,
    )
    if response.status_code != 200:
        raise RuntimeError(f"{name}: {response.status_code} {response.text[:300]}")
    print(f"OK {name}")


if __name__ == "__main__":
    print("Publishing richer Gutenberg-first staging pages...")
    for page_id, name, content in PAGES:
        publish_page(page_id, name, content)
    print("Done.")
