#!/usr/bin/env python3
"""Comprehensive site audit — SEO, content quality, images, duplicates."""
import requests, json, re, base64
from urllib.parse import urlparse

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'
SITE = 'https://www.loungenie.com/wp-json'

pages = [4701, 2989, 4862, 5139, 5285, 5223, 5668, 5651, 5686, 5716]
names = {4701:'home',2989:'features',4862:'about',5139:'contact',5285:'videos',
         5223:'gallery',5668:'investors',5651:'board',5686:'financials',5716:'press'}
urls = {4701:'https://www.loungenie.com/',2989:'https://www.loungenie.com/poolside-amenity-unit/',
        4862:'https://www.loungenie.com/hospitality-innovation/',5139:'https://www.loungenie.com/contact-loungenie/',
        5285:'https://www.loungenie.com/loungenie-videos/',5223:'https://www.loungenie.com/cabana-installation-photos/',
        5668:'https://www.loungenie.com/investors/',5651:'https://www.loungenie.com/board/',
        5686:'https://www.loungenie.com/financials/',5716:'https://www.loungenie.com/press/'}

print("=" * 70)
print("LOUNGENIE FULL SITE AUDIT")
print("=" * 70)

issues = []
page_data = {}

for pid in pages:
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    name = names.get(pid, str(pid))
    title = d.get('title', {}).get('rendered', '')
    slug = d.get('slug', '')
    status = d.get('status', '')
    content = d.get('content', {}).get('rendered', '')
    meta = d.get('meta', {}) or {}
    rm_title = meta.get('rank_math_title', '') or ''
    rm_desc = meta.get('rank_math_description', '') or ''
    rm_robots = meta.get('rank_math_robots', []) or []
    yoast_title = meta.get('_yoast_wpseo_title', '') or ''
    yoast_desc = meta.get('_yoast_wpseo_metadesc', '') or ''
    
    page_data[pid] = {'name': name, 'title': title, 'slug': slug, 'status': status,
                       'content': content, 'rm_title': rm_title, 'rm_desc': rm_desc,
                       'rm_robots': rm_robots}
    
    print(f"\n--- {name.upper()} (ID:{pid}) ---")
    print(f"  WP title:    {title[:80]}")
    print(f"  Slug:        {slug}")
    print(f"  Status:      {status}")
    print(f"  Content len: {len(content)} chars")
    print(f"  RM title:    {rm_title[:80] if rm_title else '[NONE]'}")
    print(f"  RM desc:     {rm_desc[:120] if rm_desc else '[NONE]'}")
    print(f"  RM robots:   {rm_robots}")
    
    # Flag issues
    if not rm_title:
        issues.append(f"[SEO] {name}: No Rank Math title set")
    if not rm_desc:
        issues.append(f"[SEO] {name}: No Rank Math meta description")
    if 'noindex' in str(rm_robots).lower():
        issues.append(f"[SEO] {name}: noindex flag set!")
    if status != 'publish':
        issues.append(f"[STATUS] {name}: status is '{status}' not published!")
    if len(content) < 500 and name not in ['videos', 'financials', 'press']:
        issues.append(f"[CONTENT] {name}: very thin content ({len(content)} chars)")

print("\n")
print("=" * 70)
print("ISSUES FOUND")
print("=" * 70)
if issues:
    for iss in issues:
        print(f"  {iss}")
else:
    print("  No issues detected!")

# Check rendered pages for additional problems
print("\n")
print("=" * 70)
print("RENDERED PAGE CHECKS (title tags, canonical, OG, robots)")
print("=" * 70)
for pid, url in urls.items():
    try:
        name = names[pid]
        resp = requests.get(url, timeout=20, headers={'User-Agent': 'Mozilla/5.0'})
        html = resp.text
        # Title
        m_title = re.search(r'<title[^>]*>([^<]+)</title>', html, re.I)
        m_canon = re.search(r'<link rel="canonical" href="([^"]+)"', html, re.I)
        m_robots = re.search(r'<meta name="robots" content="([^"]+)"', html, re.I)
        m_desc = re.search(r'<meta name="description" content="([^"]+)"', html, re.I)
        m_og_title = re.search(r'<meta property="og:title" content="([^"]+)"', html, re.I)
        m_og_desc = re.search(r'<meta property="og:description" content="([^"]+)"', html, re.I)
        m_og_img = re.search(r'<meta property="og:image" content="([^"]+)"', html, re.I)
        
        title_rendered = m_title.group(1).strip() if m_title else '[NONE]'
        canonical = m_canon.group(1).strip() if m_canon else '[NONE]'
        robots_val = m_robots.group(1).strip() if m_robots else '[NONE]'
        desc_val = m_desc.group(1).strip() if m_desc else '[NONE]'
        og_title = m_og_title.group(1).strip() if m_og_title else '[NONE]'
        og_desc = m_og_desc.group(1).strip() if m_og_desc else '[NONE]'
        og_img = m_og_img.group(1).strip() if m_og_img else '[NONE]'
        
        print(f"\n{name.upper()}")
        print(f"  title:     {title_rendered[:80]}")
        print(f"  canonical: {canonical}")
        print(f"  robots:    {robots_val}")
        print(f"  desc:      {desc_val[:100]}")
        print(f"  og:title:  {og_title[:80]}")
        print(f"  og:desc:   {og_desc[:100]}")
        print(f"  og:image:  {og_img[:80]}")
        
        # check for noindex
        if 'noindex' in robots_val.lower():
            print(f"  *** WARNING: noindex is SET on {name}!")
        # check canonical matches expected URL
        if canonical != url.rstrip('/') and canonical != url:
            print(f"  *** WARN: canonical [{canonical}] != expected [{url}]")
        # desc length
        if desc_val == '[NONE]' or len(desc_val) < 50:
            print(f"  *** WARN: missing/short meta description")
        if og_img == '[NONE]':
            print(f"  *** WARN: no OG image set")
            
    except Exception as e:
        print(f"  {name}: ERROR {e}")

print("\nAudit complete.")
