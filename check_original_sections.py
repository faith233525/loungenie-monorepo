import urllib.request, json, base64, re, html

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred}

# Fetch original full revision
req = urllib.request.Request(
    "https://www.loungenie.com/wp-json/wp/v2/pages/5686/revisions/5693",
    headers=hdrs
)
with urllib.request.urlopen(req) as r:
    rev = json.loads(r.read())
content = rev["content"]["rendered"]

# Find all headings
headings = re.findall(r'<h([1-6])[^>]*>(.*?)</h\1>', content, re.IGNORECASE | re.DOTALL)
print(f"Headings in original revision 5693:")
for level, text in headings:
    clean = html.unescape(re.sub(r'<[^>]+>', '', text).strip())
    print(f"  H{level}: {clean}")

# Also show context around each heading (what links follow)
print("\n\nFull structure (headings + first link after each):")
# Split by h2/h3 tags
parts = re.split(r'(<h[23][^>]*>.*?</h[23]>)', content, flags=re.IGNORECASE | re.DOTALL)
for part in parts:
    if re.match(r'<h[23]', part, re.IGNORECASE):
        clean = html.unescape(re.sub(r'<[^>]+>', '', part).strip())
        print(f"\n  SECTION: {clean}")
    else:
        # Count links in this section
        links = re.findall(r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\']', part, re.IGNORECASE)
        if links:
            print(f"    ({len(links)} PDF links)")
            # Show first link anchor
            first = re.search(r'<a[^>]+>[^<]*</a>', part, re.IGNORECASE)
