import urllib.request
import json
import base64
import re

user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
credentials = base64.b64encode(f"{user}:{app_password}".encode()).decode()
headers = {"Authorization": f"Basic {credentials}", "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5686

# Get all revisions
req = urllib.request.Request(f"{base}/pages/{page_id}/revisions?per_page=100", headers=headers)
with urllib.request.urlopen(req) as resp:
    revisions = json.loads(resp.read())

print(f"Total revisions: {len(revisions)}")

all_pdfs = {}  # url -> set of anchor texts seen

for rev in revisions:
    rid = rev['id']
    date = rev.get('date', '')[:10]
    content = rev.get('content', {}).get('rendered', '')
    length = len(content)
    
    # Find all hrefs
    hrefs = re.findall(r'href=["\']([^"\']+\.pdf[^"\']*)["\']', content, re.IGNORECASE)
    anchors = re.findall(r'href=["\'][^"\']+\.pdf[^"\']*["\'][^>]*>\s*([^<]+)\s*<', content, re.IGNORECASE)
    
    # Also find links with anchor text
    link_matches = re.findall(r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\'][^>]*>(.*?)</a>', content, re.IGNORECASE | re.DOTALL)
    
    if hrefs or link_matches:
        print(f"\nRev {rid} ({date}) [{length} chars]: {len(link_matches)} PDF links")
        for url, anchor in link_matches:
            clean_anchor = re.sub(r'<[^>]+>', '', anchor).strip()
            print(f"  [{clean_anchor}] -> {url}")
            if url not in all_pdfs:
                all_pdfs[url] = set()
            all_pdfs[url].add(clean_anchor)

print("\n" + "="*80)
print(f"UNIQUE PDF URLS FOUND ACROSS ALL REVISIONS: {len(all_pdfs)}")
print("="*80)
for url, texts in sorted(all_pdfs.items()):
    print(f"\n  URL: {url}")
    for t in texts:
        print(f"       Anchor: {t}")
