import urllib.request, json, base64, re, html

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred}

# --- FINANCIALS ---
req = urllib.request.Request("https://www.loungenie.com/wp-json/wp/v2/pages/5686", headers=hdrs)
with urllib.request.urlopen(req) as r:
    fin = json.loads(r.read())
fin_content = fin["content"]["rendered"]

fin_links = re.findall(r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\'][^>]*>(.*?)</a>', fin_content, re.IGNORECASE | re.DOTALL)
print(f"=== FINANCIALS PAGE (ID 5686) ===")
print(f"Total PDF links: {len(fin_links)}")
for url, anchor in fin_links:
    clean = html.unescape(re.sub(r'<[^>]+>', '', anchor).strip())
    print(f"  {clean}")

# --- PRESS ---
req2 = urllib.request.Request("https://www.loungenie.com/wp-json/wp/v2/pages/5716", headers=hdrs)
with urllib.request.urlopen(req2) as r:
    press = json.loads(r.read())
press_content = press["content"]["rendered"]

press_h2 = re.findall(r'<h2[^>]*>(.*?)</h2>', press_content, re.IGNORECASE | re.DOTALL)
print(f"\n=== PRESS PAGE (ID 5716) ===")
print(f"Total press releases (h2 headings): {len(press_h2)}")
for h in press_h2:
    clean = html.unescape(re.sub(r'<[^>]+>', '', h).strip())
    print(f"  {clean}")

# Also count all PDF/document links on press page
press_links = re.findall(r'href=["\']([^"\']+\.pdf[^"\']*)["\']', press_content, re.IGNORECASE)
print(f"\nPress page PDF links: {len(press_links)}")
for l in press_links:
    print(f"  {l}")
