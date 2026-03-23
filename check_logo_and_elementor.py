"""Check if elementor element rwx7f3g actually renders in the HTML body of home/videos."""
import urllib.request, re

for label, url in [('home', 'https://www.loungenie.com/loungenie/'),
                   ('videos', 'https://www.loungenie.com/loungenie/loungenie-videos/')]:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=20) as r:
        html = r.read().decode('utf-8', errors='replace')

    # Find body section (after </head>)
    head_end = html.find('</head>')
    body_html = html[head_end:] if head_end != -1 else html

    print(f'\n=== {label} ===')
    
    # Check for rwx7f3g in body  
    idx = body_html.find('rwx7f3g')
    if idx == -1:
        print('  rwx7f3g: NOT FOUND in body (the element does not render visibly)')
    else:
        print(f'  rwx7f3g FOUND in body at pos={idx+head_end}')
        print(f'  ...{body_html[max(0,idx-100):idx+300]}...')

    # Check for services9 in body (not CSS)
    s9_idx = body_html.find('services9')
    if s9_idx == -1:
        print('  services9.jpg: NOT FOUND in body HTML')
    else:
        print(f'  services9.jpg: FOUND in body at pos={s9_idx+head_end}')

    # Also check for logo images in the nav
    nav_logos = re.findall(r'<img[^>]+(?:logo|custom-logo)[^>]*>', body_html, re.I)
    print(f'  Logo imgs in body: {len(nav_logos)}')
    for logo in nav_logos[:3]:
        print(f'    {logo[:200]}')
    
    # Check for ALL img tags in the nav/header area
    nav_end = body_html.find('</header>')
    if nav_end != -1:
        nav_html = body_html[:nav_end+9]
        nav_imgs = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', nav_html, re.I)
        print(f'  All imgs in header area: {nav_imgs}')
