#!/usr/bin/env python3
"""Deep content audit - get text of each page and flag quality issues."""
import requests, base64, re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

pages = [
    (4701, 'home'),
    (2989, 'features'),
    (4862, 'about'),
    (5139, 'contact'),
    (5285, 'videos'),
    (5223, 'gallery'),
]

for pid, name in pages:
    r = requests.get(f'{BASE}/pages/{pid}', headers=hdrs, timeout=20)
    d = r.json()
    html = d.get('content', {}).get('rendered', '')
    
    # Extract text
    text = re.sub(r'<style[^>]*>.*?</style>', '', html, flags=re.DOTALL|re.I)
    text = re.sub(r'<script[^>]*>.*?</script>', '', text, flags=re.DOTALL|re.I)
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    
    # Count images in content
    img_count = len(re.findall(r'<img ', html, re.I))
    
    # Flag factual issues
    issues = []
    # Check for incorrect product claims
    if 'dual usb' in text.lower():
        issues.append("WRONG: 'dual USB' found")
    if 'insulated' in text.lower() and 'ice' in text.lower():
        issues.append("WRONG: 'insulated (ice)' found — should be 'removable'")
    if 'connects to' in text.lower() and 'pos' in text.lower():
        issues.append("WRONG: 'connects to POS' found")
    if 'works with any pos' in text.lower():
        issues.append("WRONG: 'works with any POS' found")
    if 'touchscreen' in text.lower() and 'guest' in text.lower():
        issues.append("POSSIBLE: 'touchscreen' used near 'guest' — check context")
    if 'vandal' in text.lower():
        issues.append("CHECK: 'vandal' found — we don't claim vandal deterrence")
    if 'standalone' in text.lower():
        issues.append("CHECK: 'standalone' found — LounGenie goes INTO cabanas, not standalone")
    
    word_count = len(text.split())
    
    print(f"\n{'='*60}")
    print(f"PAGE: {name.upper()} (ID:{pid})")
    print(f"  HTML: {len(html)} chars | Text words: {word_count} | Images: {img_count}")
    if issues:
        for iss in issues:
            print(f"  *** ISSUE: {iss}")
    else:
        print(f"  Content: clean (no product fact violations detected)")
    
    # Show first 600 chars of text
    print(f"\n  TEXT PREVIEW:")
    print(f"  {text[:600]}...")
    
    # Show last 200 chars
    if len(text) > 800:
        print(f"\n  ...{text[-200:]}")
