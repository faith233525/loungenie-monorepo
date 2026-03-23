#!/usr/bin/env python3
"""Apply a high-quality Gutenberg-first redesign to staging marketing pages.

Scope:
- Home (4701)
- Features (2989)
- About (4862)
- Contact (5139)
- Videos (5285)
- Gallery (5223)

Safety:
- Investors (5668) must remain unchanged.
"""

import base64
import hashlib
import json
import requests

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
HEADERS = {'Authorization': f'Basic {AUTH}', 'Content-Type': 'application/json'}

SITE = 'https://loungenie.com/staging'
IMG = f'{SITE}/wp-content/uploads/2026/03'

HOME = f'''<!-- wp:cover {{"url":"{IMG}/lg-home-hero-the-grove-7-scaled.jpg","dimRatio":56,"overlayColor":"black","isUserOverlayColor":true,"minHeight":560,"focalPoint":{{"x":0.42,"y":0.52}},"isDark":true,"className":"lg-home-hero"}} -->
<div class="wp-block-cover is-dark lg-home-hero" style="padding-top:96px;padding-right:24px;padding-bottom:96px;padding-left:24px;min-height:560px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-56 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie at premium seating" src="{IMG}/lg-home-hero-the-grove-7-scaled.jpg" style="object-position:42% 52%" data-object-fit="cover" data-object-position="42% 52%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group"><!-- wp:paragraph {{"style":{{"typography":{{"textTransform":"uppercase","letterSpacing":"2px","fontSize":"12px"}}}}}} --><p style="font-size:12px;letter-spacing:2px;text-transform:uppercase">Poolside Hospitality Innovation</p><!-- /wp:paragraph -->
<!-- wp:heading {{"level":1,"style":{{"typography":{{"fontSize":"60px","lineHeight":"1.02"}}}}}} --><h1 class="wp-block-heading" style="font-size:60px;line-height:1.02">Transform premium seating into a revenue engine.</h1><!-- /wp:heading -->
<!-- wp:paragraph {{"style":{{"typography":{{"fontSize":"21px","lineHeight":"1.85"}}}}}} --><p style="font-size:21px;line-height:1.85">LounGenie combines ordering, secure storage, charging, and guest comfort in one commercial unit installed directly into cabanas and premium seating zones.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/contact-loungenie/">Request a Demo</a></div><!-- /wp:button -->
<!-- wp:button {{"className":"is-style-outline"}} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{SITE}/poolside-amenity-unit/">See Product Features</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"64px","bottom":"56px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} --><div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:56px;padding-left:24px"><!-- wp:heading {{"textAlign":"center","level":2}} --><h2 class="wp-block-heading has-text-align-center">Trusted by hotels, resorts, waterparks, and cruise operators</h2><!-- /wp:heading -->
<!-- wp:gallery {{"linkTo":"none","columns":4,"imageCrop":false}} -->
<figure class="wp-block-gallery has-nested-images columns-4 is-cropped"><!-- wp:image --><figure class="wp-block-image"><img src="{IMG}/logo-holiday-inn.webp" alt="Holiday Inn"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="{IMG}/logo-splash-kingdom.webp" alt="Splash Kingdom"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="https://www.loungenie.com/wp-content/uploads/2025/10/logo-color.png" alt="Palace Entertainment"/></figure><!-- /wp:image --><!-- wp:image --><figure class="wp-block-image"><img src="https://www.loungenie.com/wp-content/uploads/2025/10/cowabunga-vegas-logo-300x173.png.webp" alt="Cowabunga"/></figure><!-- /wp:image --></figure>
<!-- /wp:gallery --></div><!-- /wp:group -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"20px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} --><div class="wp-block-group" style="padding-top:20px;padding-right:24px;padding-bottom:72px;padding-left:24px"><!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">One unit. Four core capabilities.</h2><!-- /wp:heading -->
<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">ORDER</h3><!-- /wp:heading --><p>Guests scan a QR code, and orders print on a dedicated PoolSafe printer.</p></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">STASH</h3><!-- /wp:heading --><p>Waterproof safe with waterproof keypad for secure storage.</p></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">CHARGE</h3><!-- /wp:heading --><p>Solar USB charging ports keep guest devices powered through long stays.</p></div><!-- /wp:column --></div><!-- /wp:columns -->
<!-- wp:paragraph --><p><strong>CHILL:</strong> A removable ice bucket adds built-in beverage convenience at the seat.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>PoolSafe installs, maintains, and services the system through a zero-upfront, revenue-share model.</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->'''

FEATURES = f'''<!-- wp:cover {{"url":"{IMG}/lg-about-westin-hilton-head-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":520,"focalPoint":{{"x":0.5,"y":0.46}},"isDark":true,"className":"lg9-page-hero"}} -->
<div class="wp-block-cover is-dark lg9-page-hero" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:520px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Product Features" src="{IMG}/lg-about-westin-hilton-head-scaled.jpg" style="object-position:50% 46%" data-object-fit="cover" data-object-position="50% 46%"/><div class="wp-block-cover__inner-container"><!-- wp:group {{"layout":{{"type":"constrained","contentSize":"1080px"}}}} -->
<div class="wp-block-group"><h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Product Features</h1><p style="font-size:20px;line-height:1.8">Built for hospitality teams that need speed, durability, and guest convenience.</p>
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/contact-loungenie/">Request a Demo</a></div></div></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><p>Designed for Real Operations</p><h2 class="wp-block-heading">Every tier is cumulative and operationally clear.</h2><p>LounGenie tiers build on each other, letting properties scale from convenience-first to full service acceleration.</p>
<div class="wp-block-columns"><div class="wp-block-column"><h3 class="wp-block-heading">Classic</h3><ul><li>STASH</li><li>CHARGE</li><li>CHILL</li></ul></div>
<div class="wp-block-column"><h3 class="wp-block-heading">Service+</h3><ul><li>Everything in Classic</li><li>Service call button</li><li>Alerts to staff touchscreen monitor</li></ul></div>
<div class="wp-block-column"><h3 class="wp-block-heading">2.0</h3><ul><li>Everything in Service+</li><li>QR ordering added</li><li>Service call button remains active</li></ul></div></div>
<h2 class="wp-block-heading">How ordering works</h2><ol><li>Guest scans QR code at seat.</li><li>Order prints on dedicated PoolSafe printer.</li><li>Staff fulfill request through existing service flow.</li></ol><p><strong>No direct POS integration is required.</strong> The printer and service-call touchscreen are separate devices with separate roles.</p>
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/cabana-installation-photos/">View Deployment Photos</a></div></div></div>
<!-- /wp:group -->'''

ABOUT = f'''<!-- wp:cover {{"url":"{IMG}/lg-about-westin-hilton-head-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":520,"focalPoint":{{"x":0.5,"y":0.36}},"isDark":true}} -->
<div class="wp-block-cover is-dark" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:520px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Hospitality Innovation" src="{IMG}/lg-about-westin-hilton-head-scaled.jpg" style="object-position:50% 36%" data-object-fit="cover" data-object-position="50% 36%"/><div class="wp-block-cover__inner-container"><div class="wp-block-group"><h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Hospitality Innovation</h1><p style="font-size:20px;line-height:1.8">Built in Canada by PoolSafe and engineered for high-use hospitality environments.</p></div></div></div>
<!-- /wp:cover -->
<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} --><div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><h2 class="wp-block-heading">What makes the platform different</h2><div class="wp-block-columns"><div class="wp-block-column"><h3 class="wp-block-heading">Commercial construction</h3><p>Solid aluminum interior, corrosion-resistant build quality, and deployment-ready durability.</p></div><div class="wp-block-column"><h3 class="wp-block-heading">Service-centered workflow</h3><p>Service call requests route to staff monitor while QR orders route to dedicated printer.</p></div><div class="wp-block-column"><h3 class="wp-block-heading">Brand flexibility</h3><p>Units can be customized to match venue colors and include logos or advertising surfaces.</p></div></div><p>From independent resorts to large multi-site operators, LounGenie helps teams standardize premium seating service while opening new revenue opportunities.</p><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/contact-loungenie/">Talk With Our Team</a></div></div></div><!-- /wp:group -->'''

CONTACT = f'''<!-- wp:cover {{"url":"{IMG}/lg-contact-owc-cabana-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.52,"y":0.30}},"isDark":true}} -->
<div class="wp-block-cover is-dark" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Contact LounGenie" src="{IMG}/lg-contact-owc-cabana-scaled.jpg" style="object-position:52% 30%" data-object-fit="cover" data-object-position="52% 30%"/><div class="wp-block-cover__inner-container"><div class="wp-block-group"><h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Contact LounGenie</h1><p style="font-size:20px;line-height:1.8">Tell us about your property and we will map a practical rollout plan.</p></div></div></div>
<!-- /wp:cover -->
<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"60px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} --><div class="wp-block-group" style="padding-top:60px;padding-right:24px;padding-bottom:72px;padding-left:24px"><div class="wp-block-columns"><div class="wp-block-column"><h2 class="wp-block-heading">Start the conversation</h2><p><strong>Email:</strong> <a href="mailto:info@poolsafeinc.com">info@poolsafeinc.com</a><br><strong>Phone:</strong> <a href="tel:+14166302444">+1 (416) 630-2444</a></p><ul><li>Property type and seating mix</li><li>Preferred rollout timeline</li><li>Classic, Service+, or 2.0 fit</li></ul></div><div class="wp-block-column"><figure class="wp-block-image"><img src="{IMG}/The-Grove-6.jpg" alt="LounGenie deployment in premium cabana zone"/></figure></div></div><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/poolside-amenity-unit/">Review Features</a></div><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{SITE}/loungenie-videos/">Watch Videos</a></div></div></div><!-- /wp:group -->'''

VIDEOS = f'''<!-- wp:cover {{"url":"{IMG}/lg-gallery-sea-world-san-diego.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.52,"y":0.44}},"isDark":true}} -->
<div class="wp-block-cover is-dark" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="LounGenie Videos" src="{IMG}/lg-gallery-sea-world-san-diego.jpg" style="object-position:52% 44%" data-object-fit="cover" data-object-position="52% 44%"/><div class="wp-block-cover__inner-container"><div class="wp-block-group"><h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">LounGenie Videos</h1><p style="font-size:20px;line-height:1.8">See deployment context, service flow, and operational readiness in real venues.</p></div></div></div>
<!-- /wp:cover -->
<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1120px"}}}} --><div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px"><h2 class="wp-block-heading">What each walkthrough should answer</h2><ul><li>How the unit fits into the seating footprint</li><li>How staff handle service alerts and orders</li><li>How the guest experience improves at the seat</li></ul><div class="wp-block-columns"><div class="wp-block-column"><figure class="wp-block-image"><img src="{IMG}/six-flags-hurricane-harbor-cabana.jpg" alt="Waterpark deployment context"/></figure></div><div class="wp-block-column"><figure class="wp-block-image"><img src="{IMG}/lg-gallery-water-world-cabana-1.jpg" alt="Resort cabana deployment context"/></figure></div></div><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/contact-loungenie/">Book a Demo Session</a></div></div></div><!-- /wp:group -->'''

GALLERY = f'''<!-- wp:cover {{"url":"{IMG}/The-Grove-6.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":500,"focalPoint":{{"x":0.5,"y":0.4}},"isDark":true}} -->
<div class="wp-block-cover is-dark" style="padding-top:84px;padding-right:24px;padding-bottom:84px;padding-left:24px;min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Cabana Installation Photos" src="{IMG}/The-Grove-6.jpg" style="object-position:50% 40%" data-object-fit="cover" data-object-position="50% 40%"/><div class="wp-block-cover__inner-container"><div class="wp-block-group"><h1 class="wp-block-heading" style="font-size:56px;line-height:1.02">Cabana Installation Photos</h1><p style="font-size:20px;line-height:1.8">Real deployment examples across properties and seating configurations.</p></div></div></div>
<!-- /wp:cover -->
<!-- wp:group {{"style":{{"spacing":{{"padding":{{"top":"64px","bottom":"72px","left":"24px","right":"24px"}}}}}},"layout":{{"type":"constrained","contentSize":"1140px"}}}} --><div class="wp-block-group" style="padding-top:64px;padding-right:24px;padding-bottom:72px;padding-left:24px"><h2 class="wp-block-heading">Deployment gallery</h2><figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><figure class="wp-block-image"><img src="{IMG}/six-flags-hurricane-harbor-cabana.jpg" alt="Six Flags Hurricane Harbor private cabana deployment with LounGenie"/></figure><figure class="wp-block-image"><img src="{IMG}/six-flags-hurricane-harbor-lineup-angle.jpg" alt="Six Flags Hurricane Harbor LounGenie units staged for deployment"/></figure><figure class="wp-block-image"><img src="{IMG}/six-flags-hurricane-harbor-lineup-front.jpg" alt="Six Flags Hurricane Harbor current-production LounGenie units"/></figure><figure class="wp-block-image"><img src="{IMG}/lg-gallery-water-world-cabana-1.jpg" alt="Water World cabana with the LounGenie"/></figure><figure class="wp-block-image"><img src="{IMG}/lg-gallery-cowabunga-cabana-1-scaled.jpg" alt="Cowabunga Bay cabana with the LounGenie"/></figure><figure class="wp-block-image"><img src="{IMG}/lg-gallery-soaky-10-scaled.jpg" alt="Soaky Mountain premium seating with the LounGenie"/></figure><figure class="wp-block-image"><img src="{IMG}/lg-gallery-hilton-kona-cabana-4-scaled.jpg" alt="Hilton Waikoloa Kona pool cabana with the LounGenie"/></figure><figure class="wp-block-image"><img src="{IMG}/lg-gallery-sea-world-san-diego.jpg" alt="Sea World San Diego premium seating with the LounGenie"/></figure></figure><p>Want recommendations for your own layout? We can provide deployment planning by seating zone and service flow.</p><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{SITE}/contact-loungenie/">Request a Deployment Plan</a></div></div></div><!-- /wp:group -->'''

PAGES = {
    4701: HOME,
    2989: FEATURES,
    4862: ABOUT,
    5139: CONTACT,
    5285: VIDEOS,
    5223: GALLERY,
}


def get_content(pid: int) -> str:
    r = requests.get(f'{BASE}/pages/{pid}?context=edit', headers=HEADERS, timeout=30)
    r.raise_for_status()
    return r.json().get('content', {}).get('raw', '')


def put_content(pid: int, content: str) -> None:
    payload = json.dumps({'content': content, 'status': 'publish'})
    r = requests.post(f'{BASE}/pages/{pid}', headers=HEADERS, data=payload, timeout=60)
    if r.status_code not in (200, 201):
        raise SystemExit(f'Update failed for {pid}: HTTP {r.status_code} {r.text[:300]}')


def sha(text: str) -> str:
    return hashlib.sha256(text.encode()).hexdigest()


def main() -> None:
    inv_before = get_content(5668)
    inv_hash_before = sha(inv_before)

    for pid, content in PAGES.items():
        put_content(pid, content)
        print('UPDATED', pid, 'len', len(content))

    inv_after = get_content(5668)
    inv_hash_after = sha(inv_after)
    print('INVESTORS_UNCHANGED', inv_hash_before == inv_hash_after)
    print('INVESTORS_HASH_BEFORE', inv_hash_before)
    print('INVESTORS_HASH_AFTER ', inv_hash_after)


if __name__ == '__main__':
    main()
