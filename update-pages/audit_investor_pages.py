#!/usr/bin/env python3
import urllib.request, json, base64, re

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Checking Investor Pages for Missing Content ===\n")

# Get investor-related pages
investor_urls = [
    'https://www.loungenie.com/investors/',
    'https://www.loungenie.com/financials/',
    'https://www.loungenie.com/board/',
    'https://www.loungenie.com/press/',
]

for page_url in investor_urls:
    page_name = page_url.split('/')[-2] or 'investors'
    print(f"Checking {page_name}...")
    
    try:
        req = urllib.request.Request(page_url, headers={'User-Agent': 'Mozilla/5.0'})
        r = urllib.request.urlopen(req, timeout=15)
        html = r.read().decode('utf-8', 'replace')
        
        # Look for common content markers
        content_markers = {
            'images': len(re.findall(r'<img[^>]*>', html)),
            'figures': len(re.findall(r'<figure[^>]*>', html)),
            'videos': len(re.findall(r'<iframe[^>]*>', html)),
            'text_blocks': len(re.findall(r'<p[^>]*>', html)),
            'headings': len(re.findall(r'<h[1-6][^>]*>', html)),
        }
        
        # Check for placeholder text or error messages
        has_error = 'error' in html.lower() or 'missing' in html.lower()
        has_placeholder = 'placeholder' in html.lower() or '[empty]' in html.lower()
        
        print(f"  Images: {content_markers['images']}")
        print(f"  Figures: {content_markers['figures']}")
        print(f"  Videos: {content_markers['videos']}")
        print(f"  Text blocks: {content_markers['text_blocks']}")
        print(f"  Headings: {content_markers['headings']}")
        
        if has_error:
            print(f"  ⚠️  ERROR/Missing detected in HTML")
        if has_placeholder:
            print(f"  ⚠️  Placeholder content detected")
        
        # Check for broken image tags
        broken_imgs = re.findall(r'<img[^>]*src=["\']([^"\']*)["\'][^>]*(?:alt=["\']([^"\']*)["\'])?[^>]*>', html)
        empty_imgs = [img for img in broken_imgs if not img[0] or img[0].startswith('data:')]
        if empty_imgs:
            print(f"  ⚠️  {len(empty_imgs)} empty/broken image tags")
        
        print()
        
    except Exception as e:
        print(f"  ERROR: {e}\n")

print("\n=== Checking WordPress Media Deleted Records ===\n")

# Check if we can find what was deleted
try:
    with open('media_ops_results.json', 'r') as f:
        results = json.loads(f.read())
        if 'deleted_ids' in results:
            deleted = results['deleted_ids']
            print(f"Total deleted media items: {len(deleted)}")
            print(f"Deleted IDs: {deleted[:20]}...")
            
            # Try to get these deleted items' metadata from trash
            print("\n=== Checking if Deleted Images Were on Investor Pages ===\n")
            
            # Get all pages and check for references to deleted IDs
            url = 'https://www.loungenie.com/wp-json/wp/v2/pages?per_page=100&_fields=id,title,featured_media,content'
            req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
            
            try:
                r = urllib.request.urlopen(req, timeout=15)
                pages = json.loads(r.read())
                
                investor_keywords = ['invest', 'financial', 'board', 'press']
                
                for page in pages:
                    page_title = page.get('title', {}).get('rendered', '')
                    featured = page.get('featured_media', 0)
                    
                    # Check if this is an investor page
                    is_investor_page = any(kw in page_title.lower() for kw in investor_keywords)
                    
                    if is_investor_page:
                        print(f"Page: {page_title}")
                        if featured in deleted:
                            print(f"  ⚠️  MISSING featured image (ID {featured})")
                        else:
                            print(f"  Featured image: {featured}")
                        
                        # Check content for deleted image references
                        content = page.get('content', {}).get('rendered', '')
                        for del_id in deleted:
                            if f'wp-image-{del_id}' in content or f'id="{del_id}"' in content:
                                print(f"  ⚠️  Referenced deleted media ID {del_id} in content")
                        print()
                        
            except Exception as e:
                print(f"Error checking pages: {e}")
        
except FileNotFoundError:
    print("No deletion history found")
except Exception as e:
    print(f"Error: {e}")

print("\n=== RECOMMENDATION ===\n")
print("If investor pages are missing images/content:")
print("  1. Check WordPress backup from before cleanup")
print("  2. Or restore individual images from trash if available")
print("  3. Or re-upload replacement images")
