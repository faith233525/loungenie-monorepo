#!/usr/bin/env python3
"""
Basic accessibility & structure audit for IR pages.
Checks:
 - H1 presence and duplicates
 - Heading level order (no jumps)
 - Images missing alt or empty alt
 - Links with target="_blank" missing rel="noopener noreferrer"
 - Tables missing <caption>
 - Presence of main/nav/header/footer landmarks
 - Inline styles containing color/background-color

Writes report to artifacts/ir-accessibility-report.json
"""
import json
import re
from pathlib import Path

try:
    import requests
    from bs4 import BeautifulSoup
except Exception:
    raise SystemExit('Please install requests and beautifulsoup4')

BASE = 'https://loungenie.com/staging'
PAGES = ['investors','board','financials','press']
ART = Path(r'c:/Users/pools/Documents/wordpress-develop/artifacts')
ART.mkdir(parents=True, exist_ok=True)
REPORT = ART / 'ir-accessibility-report.json'

reports = []

COLOR_RE = re.compile(r"color\s*:\s*[^;\"']+|background(-color)?\s*:\s*[^;\"']+", re.I)

for slug in PAGES:
    url = f"{BASE}/{slug}/"
    print('Auditing', url)
    r = requests.get(url, verify=False, timeout=20)
    page = {'slug': slug, 'url': url, 'status_code': r.status_code}
    if r.status_code != 200:
        page['error'] = f'Status {r.status_code}'
        reports.append(page)
        continue
    soup = BeautifulSoup(r.text, 'html.parser')
    issues = []
    # H1
    h1s = soup.find_all('h1')
    if not h1s:
        issues.append({'type':'missing_h1','message':'No H1 found'})
    elif len(h1s) > 1:
        issues.append({'type':'multiple_h1','message':f'{len(h1s)} H1 elements found'})
    # Heading order
    headings = soup.find_all(re.compile('^h[1-6]$'))
    last = 0
    for h in headings:
        lvl = int(h.name[1])
        if last and lvl > last + 1:
            issues.append({'type':'heading_skip','message':f'Heading jump from H{last} to H{lvl} at text: "{h.get_text(strip=True)[:40]}"'})
        last = lvl
    # Images alt
    imgs = soup.find_all('img')
    for img in imgs:
        alt = img.get('alt')
        if alt is None:
            issues.append({'type':'img_missing_alt','message':f'Image missing alt, src={img.get("src")} '})
        elif alt.strip() == '':
            issues.append({'type':'img_empty_alt','message':f'Image has empty alt, src={img.get("src")} '})
    # Links target blank rel
    links = soup.find_all('a', target='_blank')
    for a in links:
        rel = a.get('rel') or []
        if 'noopener' not in rel or 'noreferrer' not in rel:
            issues.append({'type':'link_rel_missing','message':f'Link target=_blank missing rel, href={a.get("href")} '})
    # Tables caption
    tables = soup.find_all('table')
    for t in tables:
        if not t.find('caption'):
            issues.append({'type':'table_missing_caption','message':'Table missing caption'})
    # Landmarks
    for landmark in ['main','nav','header','footer']:
        if not soup.find(landmark):
            issues.append({'type':'landmark_missing','message':f'Missing <{landmark}> landmark'})
    # Inline color styles
    styled = soup.select('[style]')
    for el in styled:
        s = el.get('style','')
        if COLOR_RE.search(s):
            issues.append({'type':'inline_color_style','message':f'Inline color style on <{el.name}>: {s[:80]} '})
    page['issues'] = issues
    reports.append(page)

REPORT.write_text(json.dumps(reports, indent=2))
print('Report written to', REPORT)
