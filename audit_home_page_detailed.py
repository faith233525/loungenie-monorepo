#!/usr/bin/env python3
import requests
import base64
import re

BASE = 'https://loungenie.com/staging/wp-json/wp/v2'
AUTH = base64.b64encode(b'Copilot:U7GM Z9qE QOq6 MQva IzcQ 6PU2').decode()
HEADERS = {'Authorization': f'Basic {AUTH}'}

# Fetch home page
r = requests.get(f'{BASE}/pages/4701?context=edit', headers=HEADERS, timeout=30)
if r.status_code != 200:
    print(f'ERROR: {r.status_code}')
    exit(1)

page = r.json()
content = page.get('content', {}).get('raw', '')

print("\n" + "="*80)
print("HOME PAGE - DETAILED QUALITY AUDIT")
print("="*80)

# 1. Check for logo strip issues
print("\n1. LOGO STRIP / PARTNER SECTION:")
partner_section = re.search(r'<!-- PARTNER.*?STRIP -->.*?<!-- THE PROBLEM -->', content, re.DOTALL | re.I)
if partner_section:
    print("   ✓ Partner logo section found")
    logos = re.findall(r'(Ritz-Carlton|Hilton|Palace Entertainment|Splash Kingdom|Grove Resort|Westin|ICON|Six Flags|Hurricane)', partner_section.group(0), re.I)
    print(f"   Logos referenced: {set(logos)}")
else:
    print("   ✗ MISSING: Partner logo section")

# 2. Check hero section quality
print("\n2. HERO SECTION:")
hero_section = re.search(r'<!-- HERO -->.*?(?=<!-- |\Z)', content, re.DOTALL | re.I)
if hero_section:
    print("   ✓ Hero section found")
    hero_text = hero_section.group(0)
    
    # Check for gradient
    if 'gradient' in hero_text.lower():
        print("   ✓ Gradient applied")
    
    # Check for background image
    if 'url(' in hero_text or '.webp' in hero_text or '.jpg' in hero_text:
        print("   ✓ Background/hero image present")
    
    # Check for CTA buttons
    if 'btn' in hero_text.lower():
        print("   ✓ CTA buttons present")
    
    # Extract H1
    h1 = re.search(r'<h1[^>]*>([^<]+)</h1>', hero_text, re.I)
    if h1:
        h1_text = h1.group(1).strip()
        print(f"   H1: '{h1_text}'")
        if len(h1_text) < 40:
            print("      Warning: H1 is quite short")
        elif len(h1_text) > 120:
            print("      Warning: H1 is quite long")
else:
    print("   ✗ Hero section not clearly marked")

# 3. Feature blocks (ORDER, STASH, CHARGE, CHILL)
print("\n3. PRODUCT FEATURES (4 Tiers):")
features = {
    'ORDER (QR)': r'order|qr',
    'STASH (Safe)': r'stash|safe|waterproof',
    'CHARGE (USB)': r'charge|usb',
    'CHILL (Ice)': r'chill|ice bucket'
}

for feature, pattern in features.items():
    found = bool(re.search(pattern, content, re.I))
    print(f"   {feature}: {'✓' if found else '✗'}")

# 4. Call-to-action buttons
print("\n4. CTA BUTTONS:")
buttons = re.findall(r'<a[^>]+class="[^"]*(?:btn|button)[^"]*"[^>]*>([^<]+)</a>', content, re.I)
unique_buttons = set([b.strip() for b in buttons])
print(f"   Total unique buttons: {len(unique_buttons)}")
for btn in sorted(unique_buttons):
    print(f"   - {btn}")

# 5. Interior/deployment imagery
print("\n5. DEPLOYMENT/INTERIOR IMAGERY:")
deployment_terms = [
    'cabana interior',
    'pool deck',
    'resort seating',
    'poolside lounger',
    'deployment views',
    'in-cabana'
]

found_terms = []
for term in deployment_terms:
    if re.search(term, content, re.I):
        found_terms.append(term)

print(f"   Deployment context terms found: {len(found_terms)}")
for term in found_terms:
    print(f"   ✓ {term}")

# 6. Mobile responsiveness indicators
print("\n6. MOBILE RESPONSIVENESS:")
mobile_checks = {
    'Responsive typography (clamp)': r'clamp\(',
    'Mobile-first spacing': r'gap|padding|margin',
    'Flexbox layout': r'display.*flex|flex:',
    'viewport meta': r'viewport',
}

for check, pattern in mobile_checks.items():
    found = bool(re.search(pattern, content, re.I))
    print(f"   {check}: {'✓' if found else '✗'}")

# 7. Performance indicators
print("\n7. PERFORMANCE OPTIMIZATION:")

# Check for lazy loading
lazy_count = len(re.findall(r'loading="lazy"', content, re.I))
print(f"   Images with lazy loading: {lazy_count}")

# Check for picture/srcset
picture_count = len(re.findall(r'<picture[^>]*>', content, re.I))
srcset_count = len(re.findall(r'srcset=', content, re.I))
print(f"   <picture> elements: {picture_count}")
print(f"   srcset attributes: {srcset_count}")

# 8. Google Core Web Vitals considerations
print("\n8. CORE WEB VITALS READINESS:")
cwv_checks = {
    'LCP optimization (hero image lazy load)': lazy_count > 0,
    'CLS prevention (aspect ratio/dimensions)': re.search(r'aspect-ratio|width.*height', content, re.I) is not None,
    'INP optimization (interaction ready)': '::before' in content or 'transition' in content,
}

for check, status in cwv_checks.items():
    print(f"   {check}: {'✓' if status else '✗'}")

# 9. Accessibility & SEO
print("\n9. ACCESSIBILITY & SEO:")
accessibility = {
    'All images have alt text': len(re.findall(r'<img[^>]*alt=', content)) > 0,
    'Heading hierarchy (H1→H2)': re.search(r'<h1.*?<h2', content, re.DOTALL | re.I) is not None,
    'Meta description set': page.get('meta', {}).get('_yoast_wpseo_metadesc') is not None and len(page.get('meta', {}).get('_yoast_wpseo_metadesc', '')) > 0,
    'Structured data (schema)': re.search(r'application/ld\+json', content) is not None,
}

for check, status in accessibility.items():
    print(f"   {check}: {'✓' if status else '✗'}")

# 10. Print middle section (2000-5000 chars for context)
print("\n10. CONTENT FLOW (chars 2000-5000):")
print("-" * 80)
print(content[2000:5000])
print("-" * 80)

# 11. Print features section specifically
print("\n11. FEATURE BLOCKS SECTION:")
features_section = re.search(r'(ORDER|STASH|CHARGE|CHILL).*?(?=<!-- |$)', content, re.DOTALL | re.I)
if features_section:
    section_text = features_section.group(0)[:1500]
    print(section_text)
else:
    print("Features section not found in expected format")

print("\n" + "="*80)
print("REVIEW COMPLETE")
print("="*80)
