import urllib.request
import json
import base64

user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
credentials = base64.b64encode(f"{user}:{app_password}".encode()).decode()
headers = {"Authorization": f"Basic {credentials}", "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5716

# Get all revisions sorted by length to find the fullest version
req = urllib.request.Request(f"{base}/pages/{page_id}/revisions?per_page=100", headers=headers)
with urllib.request.urlopen(req) as resp:
    revisions = json.loads(resp.read())

print(f"Total revisions for Press page: {len(revisions)}")
print(f"\n{'Rev ID':<10} {'Date':<14} {'Chars':>8}")
print("-" * 35)
for rev in sorted(revisions, key=lambda r: len(r.get('content',{}).get('rendered','')), reverse=True)[:15]:
    rid = rev['id']
    date = rev.get('date','')[:10]
    length = len(rev.get('content',{}).get('rendered',''))
    print(f"{rid:<10} {date:<14} {length:>8}")
