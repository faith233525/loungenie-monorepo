#!/usr/bin/env python3
import urllib.request, json, base64, re

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()
headers = {'Authorization': 'Basic ' + credentials}

# Get the investor page (raw content view)
url = 'https://loungenie.com/staging/wp-json/wp/v2/pages/5668?context=edit'
req = urllib.request.Request(url, headers=headers)

try:
    with urllib.request.urlopen(req, timeout=30) as response:
        data = json.loads(response.read())
        
    print('=== INVESTOR PAGE CONTENT AUDIT ===\n')
    print(f'Page ID: {data["id"]}')
    print(f'Title: {data["title"]["rendered"]}')
    print(f'Status: {data["status"]}')
    print(f'Modified: {data["modified"]}')
    print(f'\n--- CONTENT STRUCTURE ---\n')
    
    content = data['content']['raw']
    
    # Extract all images
    images = re.findall(r'<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"', content)
    print(f'IMAGES FOUND: {len(images)}')
    for i, (img_url, alt) in enumerate(images, 1):
        print(f'  {i}. Alt: {alt}')
        print(f'     URL: {img_url}')
        print()
    
    # Extract all links
    links = re.findall(r'<a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>', content)
    print(f'\nLINKS FOUND: {len(links)}')
    for i, (link_url, link_text) in enumerate(links, 1):
        status = 'BROKEN - placeholder' if link_url in ['#', '/#'] else 'OK'
        print(f'  {i}. [{link_text}]')
        print(f'     {link_url} [{status}]')
    
    # Check for content sections
    sections = re.findall(r'<h2[^>]*>(.*?)</h2>', content)
    print(f'\n\nMAJOR SECTIONS: {len(sections)}')
    for i, section in enumerate(sections, 1):
        clean = re.sub(r'<[^>]+>', '', section)
        print(f'  {i}. {clean}')
    
    print(f'\n\n--- CONTENT METRICS ---')
    print(f'Total characters: {len(content)}')
    print(f'Total lines: {content.count(chr(10))}')
    
    # Check compliance links (look for # placeholders)
    broken = re.findall(r'<a[^>]*href="#"[^>]*>([^<]+)</a>', content)
    if broken:
        print(f'\n⚠️  BROKEN LINKS (href="#"):')
        for link in broken:
            print(f'    - {link}')
    
except Exception as e:
    print(f'Error: {e}')
    import traceback
    traceback.print_exc()
