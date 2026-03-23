#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Detailed Content Check: Investor Pages ===\n")

# Get investor page via REST API to see actual content
page_slugs = ['investors', 'financials', 'board', 'press']

for slug in page_slugs:
    print(f"\n{'='*60}")
    print(f"Page: {slug}")
    print('='*60)
    
    # Get the page data
    url = f'https://www.loungenie.com/wp-json/wp/v2/pages?slug={slug}&_fields=id,title,featured_media,content'
    req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        pages = json.loads(r.read())
        
        if pages:
            page = pages[0]
            pid = page.get('id')
            title = page.get('title', {}).get('rendered', '')
            featured = page.get('featured_media', 0)
            content = page.get('content', {}).get('rendered', '')
            
            print(f"Title: {title}")
            print(f"ID: {pid}")
            print(f"Featured Media ID: {featured}")
            print(f"Content length: {len(content)} chars")
            
            # Check for [missing] or error tags in content
            if '[' in content and ']' in content:
                import re
                missing_tags = re.findall(r'\[([^\]]*missing[^\]]*)\]', content, re.I)
                error_tags = re.findall(r'\[([^\]]*error[^\]]*)\]', content, re.I)
                
                if missing_tags:
                    print(f"\n⚠️  MISSING TAGS FOUND:")
                    for tag in missing_tags[:5]:
                        print(f"  - [{tag}]")
                
                if error_tags:
                    print(f"\n⚠️  ERROR TAGS FOUND:")
                    for tag in error_tags[:5]:
                        print(f"  - [{tag}]")
            
            # Show first 500 chars of content
            print(f"\nContent preview (first 500 chars):")
            print("-" * 60)
            preview = content.replace('<p>', '\n').replace('</p>', '').replace('<br>', '\n').replace('<br/>', '\n')
            preview = preview.replace('<strong>', '**').replace('</strong>', '**')
            preview = preview.replace('<em>', '*').replace('</em>', '*')
            preview = preview[:500]
            print(preview[:500] if len(preview) < 500 else preview + "\n... [TRUNCATED]")
            print("-" * 60)
            
            # Check featured image status
            if featured:
                print(f"\nFeatured image status:")
                media_url = f'https://www.loungenie.com/wp-json/wp/v2/media/{featured}'
                media_req = urllib.request.Request(media_url, headers={'Authorization': 'Basic ' + credentials})
                try:
                    media_r = urllib.request.urlopen(media_req, timeout=10)
                    media_data = json.loads(media_r.read())
                    print(f"  ✓ Found: {media_data.get('title', {}).get('rendered', 'N/A')}")
                    print(f"  URL: {media_data.get('source_url', 'N/A')[-50:]}")
                except urllib.error.HTTPError as e:
                    if e.code == 404:
                        print(f"  ❌ DELETED or MISSING (404)")
                    else:
                        print(f"  ⚠️  Error {e.code}")
        else:
            print(f"Page not found")
    
    except Exception as e:
        print(f"Error: {e}")

print("\n\n=== Action Required ===\n")
print("If content shows [missing-image] or similar tags:")
print("  1. Restore from backup: https://www.loungenie.com/wp-admin/?page=restore")
print("  2. Or manually re-add missing images via Media Library")
print("  3. Check which specific images were deleted")
