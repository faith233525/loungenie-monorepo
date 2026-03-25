#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

# List of deleted IDs from media_delete_results_force.csv
deleted_ids = [
    1703, 1710, 1752, 1819, 1858, 2028, 2032, 2033, 2038, 2044,
    2045, 2056, 2057, 2058, 2061, 2062, 2063, 2066, 2067, 2072,
    2090, 2365, 2366, 3540, 3546, 3885, 4421, 5019, 5036, 9624
]

print(f"=== Checking for Deleted Media in Investor Pages ===\n")
print(f"Deleted IDs: {deleted_ids}\n")

# Get investor page IDs
page_ids = {
    'investors': 5668,
    'financials': 5686,
    'board': 5651,
    'press': 5716,
}

investor_refs = {}

for page_name, page_id in page_ids.items():
    print(f"Scanning {page_name} (ID {page_id})...")
    
    url = f'https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}?_fields=content,featured_media,title'
    req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        page = json.loads(r.read())
        
        content = page.get('content', {}).get('rendered', '')
        featured = page.get('featured_media', 0)
        
        # Check if featured media was deleted
        if featured in deleted_ids:
            if page_name not in investor_refs:
                investor_refs[page_name] = {'featured': [], 'inline': []}
            investor_refs[page_name]['featured'].append(featured)
            print(f"  ⚠️  FEATURED IMAGE WAS DELETED: ID {featured}")
        
        # Check if any deleted IDs are referenced in content
        for del_id in deleted_ids:
            # Check for wp-image-{id} pattern
            if f'wp-image-{del_id}' in content or f'id={del_id}' in content or f'wp-attachment-{del_id}' in content:
                if page_name not in investor_refs:
                    investor_refs[page_name] = {'featured': [], 'inline': []}
                investor_refs[page_name]['inline'].append(del_id)
                print(f"  ⚠️  INLINE REFERENCE TO DELETED ID: {del_id}")
        
        # Quick check - if no refs found
        if page_name not in investor_refs:
            print(f"  ✓ No deleted media referenced")
        print()
        
    except Exception as e:
        print(f"  Error: {e}\n")

if investor_refs:
    print("\n=== SUMMARY: MISSING MEDIA IN INVESTOR PAGES ===\n")
    for page, refs in investor_refs.items():
        print(f"{page}:")
        if refs['featured']:
            print(f"  Featured images deleted: {refs['featured']}")
        if refs['inline']:
            print(f"  Inline image refs deleted: {refs['inline']}")
    
    print("\n=== NEXT STEPS ===")
    print("Option 1: RESTORE from trash (if not permanently deleted)")
    print("Option 2: RE-UPLOAD replacement images")
    print("Option 3: RESTORE from backup")
else:
    print("\n✓ No deleted media was referenced in investor pages")
    print("Content appears to be intact")
