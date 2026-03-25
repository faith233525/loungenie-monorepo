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
# Get the oldest revision (last one in the list)
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5651/revisions?per_page=100"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        revisions = json.loads(response.read().decode())
    
    # Get the last (oldest) revision
    oldest = revisions[-1] if revisions else None
    
    if oldest:
        oldest_id = oldest.get('id')
        oldest_date = oldest.get('modified', 'N/A')
        oldest_content = oldest.get('content', {}).get('rendered', '')
        
        print(f"OLDEST REVISION: {oldest_id}")
        print(f"Date: {oldest_date}")
        print(f"Size: {len(oldest_content)} chars\n")
        print("Content Preview:")
        print("=" * 80)
        print(oldest_content[:2000])
        print("\n" + "=" * 80)
        
        # Also check a pre-March-21 revision around 6,800 chars
        for rev in revisions:
            if 6800 <= len(rev.get('content', {}).get('rendered', '')) <= 6900:
                pre_march_21 = rev
                break
        
        if 'pre_march_21' in locals():
            print(f"\nEARLY VERSION: Revision {pre_march_21.get('id')} ({pre_march_21.get('modified')})")
            pre_content = pre_march_21.get('content', {}).get('rendered', '')
            print(f"Size: {len(pre_content)} chars\n")
            print("Content Preview:")
            print("=" * 80)
            print(pre_content[:1500])
            print("=" * 80)
            
except Exception as e:
    print(f"Error: {e}")
