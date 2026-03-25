#!/usr/bin/env python3
import urllib.request, urllib.error, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Purging Cache via WordPress Admin ===\n")

try:
    # Method 1: Try to purge via litespeed cache plugin if installed
    url = 'https://www.loungenie.com/wp-json/litespeed-cache/v1/flush'
    req = urllib.request.Request(url, method='POST', headers={
        'Authorization': 'Basic ' + credentials,
        'Content-Type': 'application/json'
    })
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        result = json.loads(r.read())
        print("✓ LiteSpeed Cache purged via plugin API")
        print(f"  Response: {result}\n")
    except urllib.error.HTTPError as e:
        if e.code == 404:
            print("⚠️  LiteSpeed Cache plugin endpoint not found (plugin may not be active)")
        else:
            print(f"⚠️  LiteSpeed purge failed: {e.code} {e.reason}")
    
except Exception as e:
    print(f"Error: {e}")

print("\n=== Alternative: Purge via WordPress Settings ===\n")

# Method 2: Manually invalidate cache by touching a settings endpoint
try:
    url = 'https://www.loungenie.com/wp-json/wp/v2/settings'
    req = urllib.request.Request(url, method='POST', headers={
        'Authorization': 'Basic ' + credentials,
        'Content-Type': 'application/json'
    }, data=b'{"blog_charset":"UTF-8"}')
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        print("✓ Settings update sent (may trigger cache invalidation)")
    except Exception as e:
        pass

except Exception as e:
    pass

print("\n=== Quick Fix ===\n")
print("BROWSER-SIDE (Immediate):")
print("  1. Go to https://www.loungenie.com/")
print("  2. Press: Ctrl+Shift+R (hard refresh)")
print("  3. Check if images now show correctly")
print()
print("SERVER-SIDE (For all users):")
print("  Looking for cache plugin admin endpoints...")

# Check if we can access a cache status endpoint
url = 'https://www.loungenie.com/wp-json/'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    routes = json.loads(r.read())
    
    # Look for cache-related routes
    cache_endpoints = [k for k in routes.get('namespaces', []) if 'cache' in k.lower()]
    
    if cache_endpoints:
        print(f"  Found cache endpoints: {cache_endpoints}")
    else:
        print("  No cache plugin endpoints found")
        print()
        print("  Try manual purge in WordPress admin:")
        print("    -> Settings > LiteSpeed Cache > Purge All")
        print("    or visit: https://www.loungenie.com/wp-admin/admin.php?page=litespeed-cache")

except Exception as e:
    pass

print("\n=== Verification ===")
print("After cache purge, verify fresh content:")
print("  python test_images.py")
