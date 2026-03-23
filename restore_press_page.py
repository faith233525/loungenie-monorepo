#!/usr/bin/env python3
import urllib.request, json, base64, re

user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
credentials = base64.b64encode(f"{user}:{app_password}".encode()).decode()
headers = {"Authorization": f"Basic {credentials}", "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"
page_id = 5716
revision_id = 8630
page_id = 5716

print(f"Fetching revision {revision_id}...")

url = f'https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}/revisions/{revision_id}'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    rev = json.loads(r.read())
    
    # Strip the existing h1/intro paragraph and rebuild as hero
    content = rev.get('content', {}).get('rendered', '')
    print(f"Retrieved revision content: {len(content)} chars")
    h1m = re.search(r'^<h1[^>]*>.*?</h1>', content, re.IGNORECASE | re.DOTALL)
    pm = None
    body = content
    if h1m:
        rest = content[h1m.end():].lstrip()
        pm = re.match(r'<p[^>]*text-align[^>]*>.*?</p>', rest, re.IGNORECASE | re.DOTALL)
        body = rest[pm.end():].strip() if pm else rest.strip()

    styled = """<!-- wp:html -->
<style>
.press-wrap{max-width:900px;margin:0 auto;padding:0 20px 60px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
.press-hero{background:linear-gradient(135deg,#0a2a4a 0%,#1a5276 100%);color:#fff;padding:60px 40px;border-radius:12px;margin-bottom:40px;text-align:center}
.press-hero h1{font-size:36px;font-weight:800;margin:0 0 12px}
.press-hero p{font-size:17px;margin:0 auto;opacity:.85;max-width:640px}
.press-wrap h2{font-size:15px;font-weight:700;color:#0a2a4a;border-bottom:2px solid #e8ecf0;padding-bottom:8px;margin:36px 0 10px;text-transform:uppercase;letter-spacing:.3px}
.press-wrap p{font-size:15px;line-height:1.65;color:#333;margin:0 0 12px}
.press-wrap a{color:#1a73e8;text-decoration:none;font-weight:500}
.press-wrap a:hover{text-decoration:underline}
</style>
<div class="press-wrap">
  <div class="press-hero">
    <h1>&#128240; Press Releases</h1>
    <p>Stay informed on LounGenie's newest partnerships, product launches, and corporate milestones.</p>
  </div>
""" + body + """
</div>
<!-- /wp:html -->"""

    print(f"Styled page content length: {len(styled)} chars")

    # Now restore by updating the page with this content
    print("Updating current page with restored content...")

    update_url = f'https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}'
    update_req = urllib.request.Request(
        update_url,
        method='POST',
        headers={
            'Authorization': 'Basic ' + credentials,
            'Content-Type': 'application/json'
        },
        data=json.dumps({'content': styled}).encode('utf-8')
    )
    
    try:
        update_r = urllib.request.urlopen(update_req, timeout=15)
        result = json.loads(update_r.read())
        print(f"✓ SUCCESS: Press page restored!")
        print(f"  New content length: {len(result.get('content', {}).get('rendered', ''))} chars")
        print(f"  Updated at: {result.get('modified', 'N/A')}")
    except urllib.error.HTTPError as e:
        print(f"✗ Update failed: {e.code} {e.reason}")
        print(f"  Response: {e.read().decode()}")
        
except Exception as e:
    print(f"✗ Error: {e}")

print("\n=== Verification ===")
print("Check press page: https://www.loungenie.com/press/")
print("Hard refresh: Ctrl+Shift+R")
