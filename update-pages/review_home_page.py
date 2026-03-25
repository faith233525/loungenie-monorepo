#!/usr/bin/env python3
import requests
import base64
import re
import json

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode(b'Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2').decode()
HEADERS = {'Authorization': f'Basic {AUTH}'}

# Fetch home page
r = requests.get(f'{BASE}/pages/4701?context=edit', headers=HEADERS, timeout=30)
if r.status_code != 200:
    print(f'ERROR: {r.status_code}')
    print(r.text[:500])
    exit(1)

page = r.json()

print("\n" + "="*80)
print("HOME PAGE COMPREHENSIVE REVIEW")
print("="*80)

print(f"\n1. PAGE METADATA:")
print(f"   ID: {page.get('id')}")
print(f"   Title: {page.get('title', 'N/A')}")
print(f"   Slug: {page.get('slug', 'N/A')}")
print(f"   Status: {page.get('status', 'N/A')}")
print(f"   Modified: {page.get('modified', 'N/A')}")
print(f"   Type: {page.get('type', 'N/A')}")

# Get content
content = page.get('content', {}).get('raw', '')
print(f"\n2. CONTENT STRUCTURE:")
print(f"   Total length: {len(content)} characters")

# Check for H1
h1_match = re.search(r'<h1[^>]*>([^<]+)</h1>', content, re.I)
print(f"\n3. HEADING (H1) ANALYSIS:")
print(f"   H1 found: {bool(h1_match)}")
if h1_match:
    print(f"   H1 text: '{h1_match.group(1)}'")

# Count heading levels
h2_count = len(re.findall(r'<h2[^>]*>', content, re.I))
h3_count = len(re.findall(r'<h3[^>]*>', content, re.I))
print(f"   H2 tags: {h2_count}")
print(f"   H3 tags: {h3_count}")

# Check for images
img_pattern = r'<img[^>]+src="([^"]*)"[^>]*>'
images = re.findall(img_pattern, content)
print(f"\n4. IMAGE ANALYSIS:")
print(f"   Total images: {len(images)}")

# Check alt text quality
img_full = re.findall(r'<img[^>]+>', content)
alt_count = 0
missing_alt = 0
for img in img_full:
    if 'alt=' in img:
        alt_count += 1
    else:
        missing_alt += 1

print(f"   Images with alt text: {alt_count}")
print(f"   Images without alt text: {missing_alt}")

# Check for WebP
webp_count = len(re.findall(r'\.webp', content, re.I))
jpg_count = len(re.findall(r'\.jpg', content, re.I))
png_count = len(re.findall(r'\.png', content, re.I))
print(f"   WebP images: {webp_count}")
print(f"   JPG images: {jpg_count}")
print(f"   PNG images: {png_count}")

# Check for meta descriptions
meta = page.get('meta', {})
print(f"\n5. SEO METADATA:")
print(f"   Meta description: '{meta.get('_yoast_wpseo_metadesc', 'N/A')}'")
print(f"   Focus keyword: '{meta.get('_yoast_wpseo_focuskeywords', 'N/A')}'")

# Check for design CSS
css_custom = re.findall(r'--lg-\w+', content)
print(f"\n6. DESIGN SYSTEM:")
print(f"   Custom CSS properties used: {len(set(css_custom))}")
if css_custom:
    print(f"   Properties: {sorted(set(css_custom))}")

# Check for key sections
sections = {
    'Logo Strip': r'<!-- PARTNER LOGO STRIP -->|partner.*logo|Selected.*[Pp]roperty',
    'Hero': r'<!-- HERO -->|hero|hero-gradient',
    'CTA Buttons': r'<a[^>]+class="[^"]*btn',
    'Call to Action': r'cta|call to action',
    'Features': r'ORDER|STASH|CHARGE|CHILL',
    'Gallery': r'gallery|gallery-lock|deployment',
    'Testimonials': r'testimonial|quote|review'
}

print(f"\n7. KEY SECTIONS:")
for section, pattern in sections.items():
    found = bool(re.search(pattern, content, re.I))
    print(f"   {section}: {'✓' if found else '✗'}")

# Check for LounGenie product messaging
product_terms = {
    'Waterproof': r'waterproof',
    'Safe/STASH': r'safe|stash',
    'Charging': r'charge|usb',
    'Ice Bucket': r'ice bucket|chill',
    'QR Ordering': r'qr|ordering',
    'Revenue Share': r'revenue share|$0 upfront',
}

print(f"\n8. PRODUCT MESSAGING:")
for term, pattern in product_terms.items():
    found = bool(re.search(pattern, content, re.I))
    print(f"   {term}: {'✓' if found else '✗'}")

# Check for common marketing language
print(f"\n9. MARKETING LANGUAGE:")
marketing_checks = {
    'Value proposition': r'value|solution|innovative|unique',
    'Social proof': r'trusted|proven|award|leading',
    'Urgency/CTA': r'contact|request|get started|learn more',
    'Benefits focus': r'benefit|advantage|help|improve',
}

for check, pattern in marketing_checks.items():
    found = bool(re.search(pattern, content, re.I))
    print(f"   {check}: {'✓' if found else '✗'}")

# Show first 3000 chars of content for detailed inspection
print(f"\n10. CONTENT SNAPSHOT (first 3000 chars):")
print("-" * 80)
print(content[:3000])
print("-" * 80)

print(f"\n\nFull review complete. Content available for detailed analysis.")
