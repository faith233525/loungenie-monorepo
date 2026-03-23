#!/usr/bin/env python3
import urllib.request, urllib.error, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== AGGRESSIVE CACHE CLEAR ===\n")

# Method 1: Try LiteSpeed API
try:
    url = 'https://www.loungenie.com/wp-json/litespeed/v1/purge/all'
    req = urllib.request.Request(url, method='POST', headers={
        'Authorization': 'Basic ' + credentials,
        'Content-Type': 'application/json'
    })
    r = urllib.request.urlopen(req, timeout=15)
    print("✓ LiteSpeed API purge sent")
except:
    print("⚠️  LiteSpeed API not available")

# Method 2: Try WP REST purge
try:
    url = 'https://www.loungenie.com/wp-json/wp/v2/settings'
    req = urllib.request.Request(url, method='POST', headers={
        'Authorization': 'Basic ' + credentials,
        'Content-Type': 'application/json'
    }, data=b'{"blog_charset":"UTF-8"}')
    r = urllib.request.urlopen(req, timeout=15)
    print("✓ WordPress settings update sent (cache invalidate)")
except Exception as e:
    print(f"⚠️  Settings update failed: {e}")

# Method 3: Query server to refresh
try:
    urls_to_prime = [
        'https://www.loungenie.com/financials/',
        'https://www.loungenie.com/press/',
        'https://www.loungenie.com/investors/',
        'https://www.loungenie.com/board/',
    ]
    
    for url in urls_to_prime:
        print(f"\nFetching: {url.split('/')[-2]}")
        try:
            req = urllib.request.Request(url, headers={
                'User-Agent': 'Mozilla/5.0',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            })
            r = urllib.request.urlopen(req, timeout=15)
            html = r.read().decode('utf-8', 'replace')
            
            # Check if content exists
            if len(html) > 5000:
                print(f"  ✓ Page loaded: {len(html)} bytes")
                # Look for actual content markers
                if '<h1' in html or '<h2' in html or '<p>' in html:
                    print(f"  ✓ Content HTML found")
                else:
                    print(f"  ⚠️  Limited content structure")
            else:
                print(f"  ❌ Page too small: {len(html)} bytes (likely empty)")
        except Exception as e:
            print(f"  Error: {e}")

except Exception as e:
    print(f"Error fetching pages: {e}")

print("\n" + "="*60)
print("MANUAL CACHE CLEAR OPTIONS:")
print("="*60)
print("""
Option 1 - Browser Hard Refresh (immediate):
  https://www.loungenie.com/financials/ → Ctrl+Shift+R
  https://www.loungenie.com/press/ → Ctrl+Shift+R

Option 2 - Clear All Browser Cache:
  Settings > Privacy & Security > Clear browsing data
  Select: "All time" + "Cached images and files"
  Click "Clear data"

Option 3 - Cloudflare/CDN purge:
  Go to: https://dash.cloudflare.com (if using)
  Caching > Purge Cache > Purge Everything

Option 4 - WordPress Admin:
  Log in to: https://www.loungenie.com/wp-admin/
  Check: Settings > LiteSpeed Cache > Purge All
  (or similar cache plugin menu)
""")
