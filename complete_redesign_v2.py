#!/usr/bin/env python3
"""
FULL SITE REDESIGN - Production-Grade Layouts
Rich content + professional styling + compelling copy
"""

import requests
import base64
import json

STAGING = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = {'Authorization': 'Basic ' + base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()}

print('Building professional staging redesign...\n')

# HOME PAGE - Rich marketing page
home_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/lg-home-hero-the-grove-7-scaled.jpg","dimRatio":56,"overlayColor":"black","isUserOverlayColor":true,"minHeight":680,"focalPoint":{"x":0.42,"y":0.52},"isDark":true,"contentPosition":"center center","className":"lg-hero-section"} -->
<div class="wp-block-cover is-dark lg-hero-section" style="padding-top:120px;padding-right:24px;padding-bottom:120px;padding-left:24px;min-height:680px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-56"></span>
<img class="wp-block-cover__image-background" alt="Premium cabana ordering solutions" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-home-hero-the-grove-7-scaled.jpg" style="object-position:42% 52%"/>
<div class="wp-block-cover__inner-container">
<!-- wp:group {"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group">
<h1 style="font-size:72px;line-height:1;font-weight:900;margin-bottom:24px;color:#fff">Revenue in Every Cabana</h1>
<p style="font-size:24px;line-height:1.6;margin-bottom:32px;color:#fff;font-weight:500">LounGenie turns premium seating into a hospitality revenue stream. QR ordering, electronic safe, solar charging, ice service—all built in.</p>
<div class="wp-block-buttons"><div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 48px;font-weight:600">Request Demo Today</a></div></div>
</div>
<!-- /wp:group -->
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px">
<h2 style="font-size:48px;line-height:1.15;font-weight:700;margin-bottom:16px;text-align:center">Why Hospitality Teams Choose LounGenie</h2>
<p style="font-size:18px;line-height:1.8;margin-bottom:64px;text-align:center;color:#555;max-width:800px;margin-left:auto;margin-right:auto">From independent resorts to regional chains, premium properties use LounGenie to streamline cabana operations and unlock new revenue without installation costs.</p>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:24px"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg" alt="Westin deployment" style="border-radius:8px"/></figure>
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">ORDER</h3>
<p style="font-size:15px;line-height:1.7;color:#666">Guests scan QR → order prints on dedicated printer → staff fulfills via existing service flow. No POS integration required.</p>
</div>
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:24px"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-gallery-sea-world-san-diego.jpg" alt="Premium seating zone" style="border-radius:8px"/></figure>
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">STASH</h3>
<p style="font-size:15px;line-height:1.7;color:#666">Waterproof electronic safe with keypad. Guests secure valuables. Single or dual unit options. Customizable colors.</p>
</div>
<div class="wp-block-column" style="flex-basis:33%">
<figure class="wp-block-image" style="margin-bottom:24px"><img src="https://loungenie.com/staging/wp-content/uploads/2026/03/The-Grove-6.jpg" alt="Charging ports and service" style="border-radius:8px"/></figure>
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">CHARGE</h3>
<p style="font-size:15px;line-height:1.7;color:#666">Solar-powered USB charging ports keep devices powered all day. Dual mounting options for any cabana layout.</p>
</div>
</div>
<!-- /wp:columns -->

<div style="margin-top:48px;text-align:center">
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/poolside-amenity-unit/" style="padding:14px 40px">See Full Feature Set</a></div></div>
</div>
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}},"backgroundColor":"#f8f8f8"},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px;background-color:#f8f8f8">
<h2 style="font-size:48px;line-height:1.15;font-weight:700;margin-bottom:48px;text-align:center">Our Deployment Partners</h2>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column"><p style="text-align:center;font-size:16px;font-weight:600;color:#333">Premium International Resorts</p></div>
<div class="wp-block-column"><p style="text-align:center;font-size:16px;font-weight:600;color:#333">Regional Entertainment Parks</p></div>
<div class="wp-block-column"><p style="text-align:center;font-size:16px;font-weight:600;color:#333">Boutique Hotel & Spa Brands</p></div>
<div class="wp-block-column"><p style="text-align:center;font-size:16px;font-weight:600;color:#333">Water Park & Adventure Venues</p></div>
</div>
<!-- /wp:columns -->

<p style="text-align:center;margin-top:48px;font-size:14px;color:#888">Deployed at 20+ properties across North America. IAAPA-recognized innovation award winner.</p>
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"72px","bottom":"72px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:72px;padding-right:24px;padding-bottom:72px;padding-left:24px">
<h2 style="font-size:48px;line-height:1.15;font-weight:700;margin-bottom:16px;text-align:center">$0 to Deploy, Revenue to Gain</h2>
<p style="font-size:18px;line-height:1.8;margin-bottom:48px;text-align:center;color:#555;max-width:900px;margin-left:auto;margin-right:auto">No installation costs. PoolSafe handles all setup, maintenance, and service. You focus on guest experience while we handle the hardware.</p>

<!-- wp:button {"align":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 56px;font-size:18px;font-weight:600">Get Started Today</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:group -->'''

# FEATURES PAGE - Comprehensive feature guide
features_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":600,"focalPoint":{"x":0.5,"y":0.46},"isDark":true} -->
<div class="wp-block-cover is-dark" style="padding-top:100px;padding-right:24px;padding-bottom:100px;padding-left:24px;min-height:600px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52"></span>
<img class="wp-block-cover__image-background" alt="LounGenie product features" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg" style="object-position:50% 46%"/>
<div class="wp-block-cover__inner-container">
<div class="wp-block-group"><h1 style="font-size:64px;line-height:1.1;font-weight:900">Product Features</h1><p style="font-size:20px;line-height:1.8;margin-top:16px">Built for properties that need speed, durability, and guest convenience</p></div>
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px">

<h2 style="font-size:44px;font-weight:700;margin-bottom:48px;text-align:center">Tiered for Every Operation</h2>

<!-- CLASSIC TIER -->
<div style="margin-bottom:60px;border:1px solid #e0e0e0;border-radius:8px;padding:40px">
<div style="display:flex;justify-content:space-between;align-items:start">
<div style="flex:1">
<h3 style="font-size:32px;font-weight:700;margin-bottom:16px">Classic</h3>
<p style="font-size:16px;color:#666;margin-bottom:24px;line-height:1.7">Essential convenience-focused service for premium seating.</p>
<div style="margin-bottom:32px">
<p style="font-weight:600;margin-bottom:12px;font-size:16px">Includes:</p>
<ul style="margin-left:20px">
<li><strong>STASH:</strong> Waterproof electronic safe with keypad</li>
<li><strong>CHARGE:</strong> Solar-powered USB ports</li>
<li><strong>CHILL:</strong> Removable ice bucket for beverage service</li>
</ul>
</div>
<p style="color:#888;font-size:14px">Revenue share model • No upfront costs • PoolSafe installation & maintenance</p>
</div>
<div style="background-color:#f0f0f0;padding:24px;border-radius:4px;min-width:160px">
<p style="font-size:28px;font-weight:700">Entry</p>
</div>
</div>
</div>

<!-- SERVICE+ TIER -->
<div style="margin-bottom:60px;border:2px solid #2c7fbc;border-radius:8px;padding:40px;background-color:#f9fbfd">
<div style="display:flex;justify-content:space-between;align-items:start">
<div style="flex:1">
<h3 style="font-size:32px;font-weight:700;margin-bottom:16px;color:#2c7fbc">Service+</h3>
<p style="font-size:16px;color:#666;margin-bottom:24px;line-height:1.7">Classic features plus immediate service escalation.</p>
<div style="margin-bottom:32px">
<p style="font-weight:600;margin-bottom:12px;font-size:16px">Everything in Classic, plus:</p>
<ul style="margin-left:20px">
<li><strong>Service Call Button:</strong> Guests press for immediate attention</li>
<li><strong>Alert System:</strong> Dedicated staff monitor displays which cabana needs service</li>
<li><strong>Priority Response:</strong> Integrated into existing service workflows</li>
</ul>
</div>
<p style="color:#888;font-size:14px">Reduces response time • Improves guest satisfaction • Operational clarity</p>
</div>
<div style="background-color:#2c7fbc;color:white;padding:24px;border-radius:4px;min-width:160px">
<p style="font-size:28px;font-weight:700">Popular</p>
</div>
</div>
</div>

<!-- 2.0 TIER -->
<div style="margin-bottom:60px;border:1px solid #e0e0e0;border-radius:8px;padding:40px">
<div style="display:flex;justify-content:space-between;align-items:start">
<div style="flex:1">
<h3 style="font-size:32px;font-weight:700;margin-bottom:16px">2.0</h3>
<p style="font-size:16px;color:#666;margin-bottom:24px;line-height:1.7">Service+ plus QR-based food & beverage ordering.</p>
<div style="margin-bottom:32px">
<p style="font-weight:600;margin-bottom:12px;font-size:16px">Everything in Service+, plus:</p>
<ul style="margin-left:20px">
<li><strong>QR Ordering:</strong> Guests scan and place food/beverage orders from seat</li>
<li><strong>Dedicated Printer:</strong> Orders print on PoolSafe-provided device (separate from service monitor)</li>
<li><strong>Service Button Active:</strong> Call button remains for general requests</li>
<li><strong>Revenue Optimization:</strong> Increased ancillary sales from premium seating zones</li>
</ul>
</div>
<p style="color:#888;font-size:14px">Maximum revenue potential • Full operational upgrade • No POS required</p>
</div>
<div style="background-color:#f0f0f0;padding:24px;border-radius:4px;min-width:160px">
<p style="font-size:28px;font-weight:700">Advanced</p>
</div>
</div>
</div>

<h2 style="font-size:36px;font-weight:700;margin-top:80px;margin-bottom:32px;text-align:center">Universal Specifications</h2>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column">
<h4 style="font-weight:700;margin-bottom:12px">Build Quality</h4>
<ul style="font-size:14px;color:#666;line-height:1.8"><li>Solid aluminum interior</li><li>Corrosion-resistant</li><li>Commercial-grade durability</li><li>Tested for high-use environments</li></ul>
</div>
<div class="wp-block-column">
<h4 style="font-weight:700;margin-bottom:12px">Customization</h4>
<ul style="font-size:14px;color:#666;line-height:1.8"><li>Any color to match resort aesthetic</li><li>Branding options available</li><li>Logo placement on units</li><li>Advertising surfaces</li></ul>
</div>
<div class="wp-block-column">
<h4 style="font-weight:700;margin-bottom:12px">Service Model</h4>
<ul style="font-size:14px;color:#666;line-height:1.8"><li>PoolSafe handles all maintenance</li><li>Dedicated support team</li><li>Preventive service schedules</li><li>Quick-response repair</li></ul>
</div>
</div>
<!-- /wp:columns -->

<div style="margin-top:60px;text-align:center">
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 56px;font-size:16px;font-weight:600">Discuss Tier Options</a></div></div>
</div>
</div>
<!-- /wp:group -->'''

# ABOUT PAGE - Brand story and value
about_content = '''<!-- wp:cover {"url":"https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg","dimRatio":52,"overlayColor":"black","isUserOverlayColor":true,"minHeight":600,"focalPoint":{"x":0.5,"y":0.36},"isDark":true} -->
<div class="wp-block-cover is-dark" style="padding-top:100px;padding-right:24px;padding-bottom:100px;padding-left:24px;min-height:600px">
<span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-52"></span>
<img class="wp-block-cover__image-background" alt="LounGenie hospitality innovation" src="https://loungenie.com/staging/wp-content/uploads/2026/03/lg-about-westin-hilton-head-scaled.jpg" style="object-position:50% 36%"/>
<div class="wp-block-cover__inner-container">
<div class="wp-block-group"><h1 style="font-size:64px;line-height:1.1;font-weight:900">Hospitality Innovation</h1><p style="font-size:20px;line-height:1.8;margin-top:16px">Engineered in Canada for the real demands of resort operations</p></div>
</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px">

<div style="max-width:900px;margin:0 auto;margin-bottom:80px">
<h2 style="font-size:40px;font-weight:700;margin-bottom:24px">Built by PoolSafe</h2>
<p style="font-size:18px;line-height:1.8;color:#555;margin-bottom:16px">LounGenie is designed and built by PoolSafe, a Toronto-based hospitality tech company focused on maximizing value from premium seating and creating seamless guest experiences.</p>
<p style="font-size:18px;line-height:1.8;color:#555">Every feature reflects real feedback from property teams: operational simplicity, guest convenience, and proven revenue impact.</p>
</div>

<h2 style="font-size:40px;font-weight:700;margin-bottom:48px;text-align:center">What Makes LounGenie Different</h2>

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:50%;margin-bottom:32px">
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">Commercial Construction</h3>
<p style="font-size:15px;line-height:1.8;color:#666">Solid aluminum interior with corrosion-resistant coating. Tested for high-use environments and designed to last years of continuous service in demanding hospitality settings.</p>
</div>
<div class="wp-block-column" style="flex-basis:50%;margin-bottom:32px">
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">Service Architecture</h3>
<p style="font-size:15px;line-height:1.8;color:#666">Service call buttons route to a dedicated staff monitor. QR orders print on a separate PoolSafe printer. Clean separation ensures no confusion and streamlined operations.</p>
</div>
</div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns">
<div class="wp-block-column" style="flex-basis:50%;margin-bottom:32px">
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">Brand Flexibility</h3>
<p style="font-size:15px;line-height:1.8;color:#666">Units can be customized to any color and include your logos or branded advertising. LounGenie becomes an extension of your resort's brand identity.</p>
</div>
<div class="wp-block-column" style="flex-basis:50%;margin-bottom:32px">
<h3 style="font-size:24px;font-weight:700;margin-bottom:12px">Zero Friction Deployment</h3>
<p style="font-size:15px;line-height:1.8;color:#666">No installation costs. PoolSafe manages setup, training, maintenance, and support. You get to focus on running your property while we focus on the hardware.</p>
</div>
</div>
<!-- /wp:columns -->

<hr style="margin:80px 0;border:none;border-top:1px solid #ddd">

<h2 style="font-size:40px;font-weight:700;margin-bottom:16px;text-align:center">Recognition</h2>
<p style="font-size:16px;text-align:center;color:#888;margin-bottom:48px">IAAPA Brass Ring Award Recipient — honoring innovative approaches to guest experience and venue operations</p>

<div style="background-color:#f8f8f8;padding:40px;border-radius:8px;text-align:center">
<p style="font-size:16px;font-weight:600;margin-bottom:8px">Deployed at 20+ Premium Properties</p>
<p style="font-size:14px;color:#888">Trusted by independent resorts, regional chains, and entertainment venues across North America</p>
</div>

<div style="margin-top:60px;text-align:center">
<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://loungenie.com/staging/contact-loungenie/" style="padding:16px 56px;font-size:16px;font-weight:600">Talk With Our Team</a></div></div>
</div>
</div>
<!-- /wp:group -->'''

# Update pages
pages_to_update = [
    (4701, 'HOME', home_content),
    (2989, 'FEATURES', features_content),
    (4862, 'ABOUT', about_content),
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

print('\n✅ Redesign complete! Check staging site for new layout.')
