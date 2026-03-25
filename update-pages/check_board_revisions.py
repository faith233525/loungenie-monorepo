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

# Board page ID: 5651
# Get revisions for the board page
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5651/revisions"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        revisions = json.loads(response.read().decode())
        
    print(f"Found {len(revisions)} revisions for board page (ID 5651)\n")
    print(f"{'Revision ID':<12} {'Date':<25} {'Characters':<12} {'Bio Keywords':<15}")
    print("-" * 65)
    
    # Show all revisions with content analysis
    for rev in revisions:
        rev_id = rev.get('id')
        date = rev.get('modified', 'N/A')[:19]
        
        # Extract content length from revision
        content_html = rev.get('content', {}).get('rendered', '')
        content_len = len(content_html)
        
        bio_keywords = content_html.lower().count('biography') + content_html.lower().count(' bio ')
        
        print(f"{rev_id:<12} {date:<25} {content_len:<12} {bio_keywords:<15}")
    
    # Now fetch the oldest revision with most content
    if revisions:
        # Find the revision with the most content (likely the fullest version)
        max_revision = max(revisions, key=lambda x: len(x.get('content', {}).get('rendered', '')))
        max_id = max_revision.get('id')
        max_content = max_revision.get('content', {}).get('rendered', '')
        
        print(f"\n\nOldest/fullest revision {max_id} has {len(max_content)} chars")
        
        # Check for biographical patterns
        if 'biography' in max_content.lower() or 'professional' in max_content.lower() or 'experience' in max_content.lower():
            print("✓ Detailed biography keywords found in this revision!")
        else:
            print("✗ No biographical keyword patterns found in fullest revision")
            
except Exception as e:
    print(f"Error: {e}")
