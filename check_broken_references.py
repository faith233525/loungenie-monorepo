#!/usr/bin/env python3
import urllib.request, json, base64, re

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Checking for Deleted Image References in Posts ===\n")

# Get all deleted media IDs from cleanup history
deleted_ids = set()
try:
    with open('media_ops_results.json', 'r') as f:
        results = json.load(f)
        if 'deleted_ids' in results:
            deleted_ids = set(results['deleted_ids'])
            print(f"Found {len(deleted_ids)} previously deleted media IDs")
            if deleted_ids:
                print(f"  Sample deleted IDs: {list(deleted_ids)[:5]}\n")
except:
    print("No deletion history found (media_ops_results.json not available)\n")

# Get all posts and check for image references
print("=== Scanning Posts for Media Attachments ===\n")

page = 1
broken_refs = []

try:
    while True:
        url = f'https://www.loungenie.com/wp-json/wp/v2/posts?per_page=100&page={page}&_fields=id,title,featured_media'
        req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
        
        try:
            r = urllib.request.urlopen(req, timeout=15)
            posts = json.loads(r.read())
            
            if not posts:
                break
            
            for post in posts:
                pid = post.get('id')
                title = post.get('title', {}).get('rendered', f'Post {pid}')[:40]
                featured = post.get('featured_media', 0)
                
                if featured and featured in deleted_ids:
                    broken_refs.append({
                        'post_id': pid,
                        'title': title,
                        'featured_media_id': featured,
                        'type': 'featured image'
                    })
            
            page += 1
        except urllib.error.HTTPError as e:
            if e.code == 400:
                break
            raise

except Exception as e:
    print(f"Error scanning posts: {e}\n")

if broken_refs:
    print(f"⚠️  FOUND {len(broken_refs)} POSTS WITH DELETED IMAGE REFERENCES:\n")
    for ref in broken_refs[:10]:
        print(f"  Post {ref['post_id']}: '{ref['title']}'")
        print(f"    -> References deleted media ID {ref['featured_media_id']} ({ref['type']})")
    
    if len(broken_refs) > 10:
        print(f"  ... and {len(broken_refs) - 10} more")
    
    print("\n⚠️  ACTION NEEDED:")
    print("  1. Upload replacement image OR")
    print("  2. Use different featured image OR")
    print("  3. Remove featured image from post")
else:
    print("✓ No broken image references found in posts")

print("\n=== Solution ===\n")
print("To fix stale page display:")
print("  1. Purge cache: python scan_and_purge_caches.py")
print("  2. Hard refresh in browser: Ctrl+Shift+R")
print("  3. Or wait 10 minutes for natural cache expiration")
