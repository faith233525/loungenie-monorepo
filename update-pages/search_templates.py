"""Search Elementor library templates and widgets for broken images."""
import urllib.request, json, base64

user = 'admin'
pw = 'i6IM cqLZ vQDC pIRk nKFr g35i'
creds = base64.b64encode((user+':'+pw).encode()).decode()
headers = {'Authorization': 'Basic ' + creds}
base = 'https://www.loungenie.com/wp-json/wp/v2'

broken_targets = ['services9', '1746838260534', '1719134653716', 'IMG_2071', 'IMG_2073']

# Check Elementor library (elementor_library CPT)
print('=== Checking Elementor library templates ===')
for post_type in ['elementor_library', 'wp_template', 'wp_template_part', 'wp_global_styles']:
    try:
        url = f'{base}/{post_type}?per_page=100&context=edit&_fields=id,slug,title,content,status'
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=10) as r:
            items = json.loads(r.read())
        print(f'\n  {post_type}: {len(items)} items')
        for item in items:
            raw = item.get('content', {}).get('raw', '') or ''
            found = [t for t in broken_targets if t in raw]
            # Also check if has elementor-5285 or services9 or investors image
            if found:
                title = item.get('title', {}).get('rendered', '') or item.get('slug', '')
                print(f'    [MATCH] id={item["id"]} {title}: {found}')
            # Look for pages that have elementor-like structure with our targets
    except urllib.error.HTTPError as e:
        print(f'  {post_type}: HTTP {e.code}')
    except Exception as e:
        print(f'  {post_type}: {str(e)[:80]}')

# Check sidebars/widgets via WP sidebars REST API
print('\n=== Checking sidebars/widgets ===')
try:
    req = urllib.request.Request('https://www.loungenie.com/wp-json/wp/v2/sidebars?context=edit', headers=headers)
    with urllib.request.urlopen(req, timeout=10) as r:
        sidebars = json.loads(r.read())
    print(f'Found {len(sidebars)} sidebars')
    for sb in sidebars:
        print(f'  sidebar: {sb.get("id")} - {sb.get("description","")[:60]}')
except Exception as e:
    print(f'Sidebars error: {e}')

# Check widgets
try:
    req = urllib.request.Request('https://www.loungenie.com/wp-json/wp/v2/widgets?context=edit', headers=headers)
    with urllib.request.urlopen(req, timeout=10) as r:
        widgets = json.loads(r.read())
    print(f'\n=== {len(widgets)} widgets ===')
    for w in widgets:
        raw = str(w)
        found = [t for t in broken_targets if t in raw]
        if found:
            print(f'  [MATCH] widget id={w.get("id")}: {found}')
except Exception as e:
    print(f'Widgets error: {e}')
