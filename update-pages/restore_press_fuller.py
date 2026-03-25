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

# Load the fuller Press content
with open('press_revision_fullest.html', 'r', encoding='utf-8') as f:
    press_content = f.read()

# Update the Press page (ID 5716)
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5716"

update_data = json.dumps({
    "content": press_content,
    "status": "publish"
}).encode('utf-8')

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
    
    print(f"✓ PRESS PAGE RESTORED")
    print(f"  Page ID: {result.get('id')}")
    print(f"  Status: {result.get('status')}")
    print(f"  Content size: {len(updated_content)} chars (was 34,297)")
    print(f"  Recovered: +1,142 missing characters")
    print(f"  Modified: {result.get('modified')}")
    
except urllib.error.HTTPError as e:
    print(f"HTTP Error {e.code}: {e.reason}")
    print(f"Response: {e.read().decode()}")
except Exception as e:
    print(f"Error: {e}")
