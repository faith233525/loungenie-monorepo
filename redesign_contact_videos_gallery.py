#!/usr/bin/env python3
"""
Complete redesign of Contact, Videos, Gallery pages
"""

import requests
import base64

STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

print('Building professional redesign for Contact/Videos/Gallery...\n')

# CONTACT PAGE
contact_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/lg-contact-owc-cabana-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":600,"focalPoint":{"x":0.52,"y":0.3},"isDark":true} -->
<div class="wp-block-cover is-dark" style="padding-top:100px;padding-right:24px;padding-bottom:100px;padding-left:24px;min-height:600px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52"></span>
<img class="wp-block-cover__image-background" alt="Contact LounGenie team" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-contact-owc-cabana-scaled.jpg" style="object-position:52% 30%"/>
<div class="wp-block-cover__inner-container">
<div class="wp-block-group"><h1 style="font-size:64px;line-height:1.1;font-weight:900">Contact LounGenie</h1><p style="font-size:20px;line-height:1.8;margin-top:16px">Let's talk about your property and map a deployment plan</p></div>
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px">

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:50%">
<h2 style="font-size:36px;font-weight:700;margin-bottom:32px">Reach Out Today</h2>

<p style="margin-bottom:24px"><strong style="font-size:18px">Email</strong><br><a href="mailto:info@poolsafeinc.com" style="color:#2c7fbc;text-decoration:none;font-size:16px">info@poolsafeinc.com</a></p>

<p style="margin-bottom:48px"><strong style="font-size:18px">Phone</strong><br><a href="tel:+14166302444" style="color:#2c7fbc;text-decoration:none;font-size:16px">+1 (416) 630-2444</a></p>

<h3 style="font-size:20px;font-weight:700;margin-bottom:16px">What to Have Ready</h3>
<ul style="font-size:15px;line-height:1.8;color:#555;margin-bottom:32px">
<li><strong>Property type</strong> - resort, water park, entertainment venue, hotel</li>
<li><strong>Current seating mix</strong> - cabanas, daybeds, lounges, other premium areas</li>
<li><strong>Timeline</strong> - when you're ready to deploy</li>
<li><strong>Tier preference</strong> - Classic, Service+, or 2.0</li>
<li><strong>Customization needs</strong> - colors, branding, logos</li>
</ul>

<p style="color:#888;font-size:14px">Our team will discuss options, answer questions, and if it's a fit, create a rollout timeline specific to your property.</p>
</div>

<div class="wp-block-column" style="flex-basis:50%">
<figure class="wp-block-image" style="margin-bottom:24px;border-radius:8px;overflow:hidden"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/The-Grove-6.jpg" alt="LounGenie in premium seating zone" style="width:100%;border-radius:8px"/></figure>
<p style="font-size:14px;color:#888;text-align:center">Real LounGenie deployment ready to serve guests</p>
</div>
</div>
<!-- /wp:columns -->

<hr style="margin:60px 0;border:none;border-top:1px solid #ddd">

<h2 style="font-size:36px;font-weight:700;margin-bottom:48px;text-align:center">Next Steps</h2>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:33%;text-align:center">
<div style="background-color:#f0f0f0;width:60px;height:60px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700">1</div>
<h3 style="font-weight:700;margin-bottom:8px">Reach Out</h3>
<p style="font-size:14px;color:#666">Email or call to start conversation about your property</p>
</div>
<div class="wp-block-column" style="flex-basis:33%;text-align:center">
<div style="background-color:#f0f0f0;width:60px;height:60px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700">2</div>
<h3 style="font-weight:700;margin-bottom:8px">Discovery Call</h3>
<p style="font-size:14px;color:#666">Discuss property type, seating mix, goals, and timeline</p>
</div>
<div class="wp-block-column" style="flex-basis:33%;text-align:center">
<div style="background-color:#f0f0f0;width:60px;height:60px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700">3</div>
<h3 style="font-weight:700;margin-bottom:8px">Deploy</h3>
<p style="font-size:14px;color:#666">PoolSafe manages all installation and support</p>
</div>
</div>
<!-- /wp:columns -->

<div style="margin-top:60px;text-align:center">
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/poolside-amenity-unit/" style="padding:16px 56px;font-size:16px;font-weight:600">Explore Features</a></div></div>
</div>

</div>
<!-- /wp:group -->'''

# VIDEOS PAGE
videos_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/lg-gallery-sea-world-san-diego.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":600,"focalPoint":{"x":0.52,"y":0.44},"isDark":true} -->
<div class="wp-block-cover is-dark" style="padding-top:100px;padding-right:24px;padding-bottom:100px;padding-left:24px;min-height:600px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52"></span>
<img class="wp-block-cover__image-background" alt="LounGenie video tours" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-gallery-sea-world-san-diego.jpg" style="object-position:52% 44%"/>
<div class="wp-block-cover__inner-container">
<div class="wp-block-group"><h1 style="font-size:64px;line-height:1.1;font-weight:900">Video Gallery</h1><p style="font-size:20px;line-height:1.8;margin-top:16px">See LounGenie in action at real properties</p></div>
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px">

<h2 style="font-size:40px;font-weight:700;margin-bottom:24px;text-align:center">Installation, Setup & Operations</h2>
<p style="font-size:18px;line-height:1.8;color:#555;margin-bottom:60px;text-align:center;max-width:800px;margin-left:auto;margin-right:auto">Watch real LounGenie deployments, see how staff interact with the platform, and understand the complete guest experience from ordering to service.</p>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column">
<div style="background-color:#f0f0f0;padding:24px;border-radius:8px;margin-bottom:24px;text-align:center;min-height:200px;display:flex;flex-direction:column;justify-content:center">
<p style="font-size:48px;margin:0;color:#999">🎬</p>
<p style="margin-top:16px;font-size:14px;color:#888">Video content coming soon</p>
</div>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">Installation Tour</h3>
<p style="font-size:14px;color:#666;line-height:1.6">See how PoolSafe deploys LounGenie in premium seating zones and integrates with existing venue operations</p>
</div>
<div class="wp-block-column">
<div style="background-color:#f0f0f0;padding:24px;border-radius:8px;margin-bottom:24px;text-align:center;min-height:200px;display:flex;flex-direction:column;justify-content:center">
<p style="font-size:48px;margin:0;color:#999">🎬</p>
<p style="margin-top:16px;font-size:14px;color:#888">Video content coming soon</p>
</div>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">Guest Experience</h3>
<p style="font-size:14px;color:#666;line-height:1.6">Watch how guests use QR ordering, access the safe, and charge devices during their stay</p>
</div>
<div class="wp-block-column">
<div style="background-color:#f0f0f0;padding:24px;border-radius:8px;margin-bottom:24px;text-align:center;min-height:200px;display:flex;flex-direction:column;justify-content:center">
<p style="font-size:48px;margin:0;color:#999">🎬</p>
<p style="margin-top:16px;font-size:14px;color:#888">Video content coming soon</p>
</div>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">Staff Operations</h3>
<p style="font-size:14px;color:#666;line-height:1.6">Learn how your team manages orders, service requests, and device maintenance with LounGenie</p>
</div>
</div>
<!-- /wp:columns -->

<div style="margin-top:60px;text-align:center">
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 56px;font-size:16px;font-weight:600">Request a Demo</a></div></div>
</div>

</div>
<!-- /wp:group -->'''

# GALLERY PAGE
gallery_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/The-Grove-6.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":600,"focalPoint":{"x":0.5,"y":0.4},"isDark":true} -->
<div class="wp-block-cover is-dark" style="padding-top:100px;padding-right:24px;padding-bottom:100px;padding-left:24px;min-height:600px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52"></span>
<img class="wp-block-cover__image-background" alt="LounGenie photo gallery" src="https://loungenie.com/staging/wp-content/uploads/2026/03/The-Grove-6.jpg" style="object-position:50% 40%"/>
<div class="wp-block-cover__inner-container">
<div class="wp-block-group"><h1 style="font-size:64px;line-height:1.1;font-weight:900">Photo Gallery</h1><p style="font-size:20px;line-height:1.8;margin-top:16px">LounGenie deployed across North America</p></div>
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1400px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px">

<h2 style="font-size:40px;font-weight:700;margin-bottom:60px;text-align:center">Deployments in Real Property Environments</h2>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:16px;border-radius:8px;overflow:hidden"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg" alt="Westin Hilton Head resort" style="width:100%;border-radius:8px"/></figure>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">Westin Hilton Head</h3>
<p style="font-size:14px;color:#666">Premium cabana seating with integrated STASH and CHARGE</p>
</div>
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:16px;border-radius:8px;overflow:hidden"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-gallery-sea-world-san-diego.jpg" alt="SeaWorld San Diego" style="width:100%;border-radius:8px"/></figure>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">SeaWorld San Diego</h3>
<p style="font-size:14px;color:#666">Premium lounges with QR ordering and service alerts</p>
</div>
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:16px;border-radius:8px;overflow:hidden"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/The-Grove-6.jpg" alt="The Grove Resort" style="width:100%;border-radius:8px"/></figure>
<h3 style="font-size:18px;font-weight:700;margin-bottom:8px">The Grove Resort</h3>
<p style="font-size:14px;color:#666">Cabana zone with full 2.0 service tier features</p>
</div>
</div>
<!-- /wp:columns -->

<div style="margin-top:60px;text-align:center;background-color:#f8f8f8;padding:40px;border-radius:8px">
<h2 style="font-size:28px;font-weight:700;margin-bottom:16px">Ready to See LounGenie at Your Property?</h2>
<p style="font-size:16px;color:#666;margin-bottom:24px">Our team maps a deployment plan tailored to your specific seating zones and revenue goals.</p>
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 56px;font-size:16px;font-weight:600">Start Conversation</a></div></div>
</div>

</div>
<!-- /wp:group -->'''

# Update pages
pages_to_update = [
    (5139, 'CONTACT', contact_content),
    (5285, 'VIDEOS', videos_content),
    (5223, 'GALLERY', gallery_content),
]

for page_id, name, content in pages_to_update:
    payload = {
        'content': content,
        'status': 'publish'
    }
    
    r = requests.post(
        f'{STAGING}/pages/{page_id}',
        headers=AUTH,
        json=payload,
        timeout=60
    )
    
    if r.status_code == 200:
        print(f'✅ {name}: Updated successfully')
    else:
        print(f'❌ {name}: {r.status_code} - {r.text[:200]}')

print('\n✅ All pages redesigned! Go to https://loungenie.com/staging/ to see the site.')
