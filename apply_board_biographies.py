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
    "Authorization": f"Basic {auth_str}",
    "Content-Type": "application/json"
}

# Read the full biographical content from revision 7609
with open('board_revision_7609_full.html', 'r', encoding='utf-8') as f:
    bio_content = f.read()

# Prepare the update payload
update_data = json.dumps({
    "content": bio_content,
    "status": "publish"
}).encode('utf-8')

# Update the board page (ID 5651) with full biographical content
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5651"

try:
    req = urllib.request.Request(
        url,
        data=update_data,
        headers=headers,
        method="POST"
    )
    
    with urllib.request.urlopen(req, timeout=10) as response:
        result = json.loads(response.read().decode())
    
    updated_content = result.get('content', {}).get('rendered', '')
    
    print(f"✓ Board page RESTORED with full member biographies")
    print(f"  Page ID: {result.get('id')}")
    print(f"  Status: {result.get('status')}")
    print(f"  Content size: {len(updated_content)} chars")
    print(f"  Modified: {result.get('modified')}")
    
    # Verify restoration
    members = ["David Berger", "Steven Glaser", "Steven Mintz", "Gillian Deacon", "Robert Pratt"]
    print(f"\n✓ Verified member biographies restored:")
    for member in members:
        if member in updated_content:
            # Find the bio paragraph
            start = updated_content.find(f"<h6>{member}")
            if start > -1:
                end = updated_content.find("</p>", start + 200)
                snippet = updated_content[start:min(end+4, start+300)]
                print(f"  ✓ {member}")
    
except urllib.error.HTTPError as e:
    print(f"HTTP Error {e.code}: {e.reason}")
    print(f"Response: {e.read().decode()}")
except Exception as e:
    print(f"Error: {e}")
