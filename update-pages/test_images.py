#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

# Test API access
print("=== Testing Media REST API ===")
url = 'https://www.loungenie.com/wp-json/wp/v2/media?per_page=5'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})
try:
    r = urllib.request.urlopen(req, timeout=15)
    data = json.loads(r.read())
    print(f"API Response: {len(data)} items")
    if data:
        for i, m in enumerate(data[:3]):
            print(f"\n[{i}] ID={m.get('id')}")
            print(f"    source_url: {m.get('source_url')}")
            print(f"    alt_text: {m.get('alt_text', '[empty]')}")
            print(f"    Checking URL status...")
            try:
                r2 = urllib.request.urlopen(m.get('source_url'), timeout=10)
                print(f"    Status: {r2.status} OK")
            except urllib.error.HTTPError as e:
                print(f"    Status: {e.code} {e.reason}")
            except Exception as e:
                print(f"    Error: {e}")
except Exception as e:
    print(f"API Error: {e}")

# Check media directory on FTP
print("\n=== Checking FTP Media Directory ===")
import ftplib
try:
    ftp = ftplib.FTP('ftp.poolsafeinc.com')
    ftp.login('copilot@loungenie.com', 'your_password_here')
    ftp.cwd('public_html/wp-content/uploads')
    items = ftp.nlst()
    print(f"Found {len(items)} items in uploads/")
    print(f"Sample: {items[:5]}")
    ftp.quit()
except Exception as e:
    print(f"FTP Check failed (expected): {e}")
