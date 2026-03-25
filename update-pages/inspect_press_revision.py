import urllib.request
import json
import base64
import re

user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
credentials = base64.b64encode(f"{user}:{app_password}".encode()).decode()
headers = {"Authorization": f"Basic {credentials}", "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5716

# Fetch the fullest revision (8630 = most recent at 35,439 chars)
req = urllib.request.Request(f"{base}/pages/{page_id}/revisions/8630", headers=headers)
with urllib.request.urlopen(req) as resp:
    rev = json.loads(resp.read())
content = rev["content"]["rendered"]
print(f"Revision 8630 content length: {len(content)}")

# Check what sections/headings are in the content
headings = re.findall(r'<h[1-6][^>]*>(.*?)</h[1-6]>', content, re.IGNORECASE | re.DOTALL)
print(f"\nHeadings found: {len(headings)}")
for h in headings[:30]:
    clean = re.sub(r'<[^>]+>', '', h).strip()
    print(f"  · {clean}")

# Check for links
links = re.findall(r'<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)</a>', content, re.IGNORECASE | re.DOTALL)
print(f"\nTotal links: {len(links)}")

# Show first 500 chars of raw content
print(f"\nFirst 800 chars of content:")
print(content[:800])
