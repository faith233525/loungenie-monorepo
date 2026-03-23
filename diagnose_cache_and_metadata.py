#!/usr/bin/env python3
import urllib.request, json, base64, hashlib

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Checking Image Metadata in REST API ===\n")

# Get media items with alt_text
url = 'https://www.loungenie.com/wp-json/wp/v2/media?per_page=20&orderby=date&order=desc'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    data = json.loads(r.read())
    
    missing_alt = []
    for m in data:
        mid = m.get('id')
        src = m.get('source_url', '')
        alt = m.get('alt_text', '')
        title = m.get('title', {}).get('rendered', '')
        
        # Check if alt_text is empty or placeholder
        if not alt or alt == 'Placeholder':
            missing_alt.append({
                'id': mid,
                'url': src[-50:],  # Last 50 chars
                'alt': alt or '[EMPTY]',
                'title': title or '[NO TITLE]'
            })
    
    print(f"Scanned {len(data)} recent media items")
    print(f"Found {len(missing_alt)} with missing/empty alt_text:\n")
    
    for item in missing_alt[:10]:
        print(f"  ID {item['id']}: alt='{item['alt']}' | url=...{item['url']}")
        print(f"           title='{item['title']}'")
    
    if len(missing_alt) > 10:
        print(f"  ... and {len(missing_alt) - 10} more")
    
except Exception as e:
    print(f"API Error: {e}")

print("\n=== Checking Cache Status ===\n")

# Check response headers for caching
test_url = 'https://www.loungenie.com/'
print(f"Fetching {test_url}")
print("Response headers:")

try:
    req = urllib.request.Request(test_url, headers={'User-Agent': 'Mozilla/5.0'})
    r = urllib.request.urlopen(req, timeout=15)
    
    cache_headers = {
        'Cache-Control': r.headers.get('Cache-Control', '[NOT SET]'),
        'Pragma': r.headers.get('Pragma', '[NOT SET]'),
        'X-Cache': r.headers.get('X-Cache', '[NOT SET]'),
        'Age': r.headers.get('Age', '[NOT SET]'),
        'ETag': r.headers.get('ETag', '[NOT SET]'),
        'Last-Modified': r.headers.get('Last-Modified', '[NOT SET]'),
    }
    
    for k, v in cache_headers.items():
        print(f"  {k}: {v}")
    
except Exception as e:
    print(f"Error fetching headers: {e}")

print("\n=== Recommendations ===\n")
print("IF MULTIPLE IMAGES MISSING ALT TEXT:")
print("  1. Run: python refresh_media_audit.py")
print("  2. Run: ./build_media_plans.ps1")
print("  3. Run: ./apply_media_updates.ps1 (apply metadata)")
print()
print("IF CACHE IS THE ISSUE (Age header > 0, X-Cache header present):")
print("  1. Purge LiteSpeed Cache: python scan_and_purge_caches.py")
print("  2. Check CDN settings in WordPress")
print("  3. Clear browser cache (Ctrl+Shift+Del)")
print()
print("IF BOTH:")
print("  1. First purge caches")
print("  2. Then refresh and apply metadata")
print("  3. Then verify fresh alt_text on pages")
