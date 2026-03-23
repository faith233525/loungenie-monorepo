#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== Investor Pages Modification History ===\n")

# Get page revisions to see what changed
page_ids = {
    'investors': 5668,
    'financials': 5686,
    'board': 5651,
    'press': 5716,
}

for page_name, page_id in page_ids.items():
    print(f"\n{page_name.upper()} (ID {page_id})")
    print("-" * 50)
    
    # Get revisions
    url = f'https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}/revisions?per_page=5&orderby=date&order=desc'
    req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        revisions = json.loads(r.read())
        
        if revisions:
            print(f"Found {len(revisions)} recent revisions:")
            for i, rev in enumerate(revisions[:3], 1):
                rev_id = rev.get('id')
                date = rev.get('date', 'N/A')
                author = rev.get('author', 'N/A')
                # Try to get author name
                try:
                    author_id = rev.get('author')
                    author_url = f'https://www.loungenie.com/wp-json/wp/v2/users/{author_id}'
                    author_req = urllib.request.Request(author_url, headers={'Authorization': 'Basic ' + credentials})
                    author_r = urllib.request.urlopen(author_req, timeout=10)
                    author_data = json.loads(author_r.read())
                    author_name = author_data.get('name', f'User {author}')
                except:
                    author_name = f'User {author}'
                
                content = rev.get('content', {}).get('rendered', '')
                content_len = len(content)
                
                print(f"  {i}. Rev #{rev_id}: {date[:10]} | {author_name} | {content_len} chars")
        else:
            print("  No revisions found")
        
    except Exception as e:
        print(f"  Error: {e}")

print("\n\n=== What This Means ===")
print("If recent revisions show content REMOVAL (char count decreased):")
print("  -> Use WordPress 'Restore' to go back to previous version")
print("\nIf no revisions exist or all show same content:")
print("  -> Pages may have been created incomplete or")
print("  -> Revisions were purged")
print("\n=== Solution ===")
print("1. Check WordPress admin: Pages > [page] > Revisions")
print("        Login to: https://www.loungenie.com/wp-admin/")
print("2. Or restore individual pages from backup")
print("3. Or notify me of what content SHOULD be on each page")
