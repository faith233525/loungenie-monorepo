import urllib.request
import urllib.error
import json
import base64

# Auth credentials
user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
auth_str = base64.b64encode(f"{user}:{app_password}".encode()).decode()

headers = {
    "Accept": "application/json",
    "Authorization": f"Basic {auth_str}"
}

# Get all Press revisions to find the one with 35,439 chars
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5716/revisions?per_page=100"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        revisions = json.loads(response.read().decode())
    
    # Find the revision with max content
    max_revision = None
    max_size = 0
    for rev in revisions:
        content = rev.get('content', {}).get('rendered', '')
        size = len(content)
        if size > max_size:
            max_size = size
            max_revision = rev
    
    if max_revision:
        rev_id = max_revision.get('id')
        date = max_revision.get('modified')
        content = max_revision.get('content', {}).get('rendered', '')
        
        print(f"✓ FULLER PRESS PAGE VERSION FOUND")
        print(f"  Revision ID: {rev_id}")
        print(f"  Date: {date}")
        print(f"  Size: {len(content)} chars (current: 34,297)")
        print(f"  Difference: +{len(content) - 34297} chars missing from current version")
        
        # Save it
        with open('press_revision_fullest.html', 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"\n✓ Saved to press_revision_fullest.html")
        
        # Check what's in it - look for press release titles
        import re
        h3_matches = re.findall(r'<h[23][^>]*>([^<]+)</h[23]>', content)
        print(f"\nContent sections found ({len(h3_matches)}):")
        for i, heading in enumerate(h3_matches[:8], 1):
            heading_clean = heading.replace('&amp;', '&').replace('</h[23]>', '').strip()
            print(f"  {i}. {heading_clean[:60]}")
    
except Exception as e:
    print(f"Error: {e}")
