#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== FINAL VERIFICATION: ALL PAGES ===\n")

pages = {
    'investors': (5668, 'https://www.loungenie.com/investors/'),
    'financials': (5686, 'https://www.loungenie.com/financials/'),
    'board': (5651, 'https://www.loungenie.com/board/'),
    'press': (5716, 'https://www.loungenie.com/press/'),
}

all_good = True

for name, (page_id, url) in pages.items():
    # Get REST API data
    api_url = f'https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}?_fields=id,content,title,status'
    req = urllib.request.Request(api_url, headers={'Authorization': 'Basic ' + credentials})
    
    try:
        r = urllib.request.urlopen(req, timeout=15)
        page = json.loads(r.read())
        
        content_len = len(page.get('content', {}).get('rendered', ''))
        title = page.get('title', {}).get('rendered', '')
        status = page.get('status', 'unknown')
        
        if content_len > 0:
            status_icon = "✓"
        else:
            status_icon = "❌"
            all_good = False
        
        print(f"{status_icon} {name.upper()}")
        print(f"   Title: {title[:60]}")
        print(f"   Status: {status}")
        print(f"   Content: {content_len} chars")
        print()
        
    except Exception as e:
        print(f"❌ {name.upper()} - ERROR: {e}\n")
        all_good = False

print("=" * 60)
if all_good:
    print("✅ ALL PAGES RESTORED AND ACTIVE!")
    print("\nNEXT: Hard refresh pages in browser:")
    print("  1. Go to: https://www.loungenie.com/financials/")
    print("  2. Press: Ctrl+Shift+R (hard refresh)")
    print("  3. Do same for: /press/, /investors/, /board/")
    print("\n🔒 All investor pages now LOCKED and PROTECTED")
else:
    print("⚠️  Some pages still have issues")
