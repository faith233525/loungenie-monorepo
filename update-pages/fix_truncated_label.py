import urllib.request, json, base64

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred, "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"

req = urllib.request.Request(f"{base}/pages/5686", headers=hdrs)
with urllib.request.urlopen(req) as r:
    page = json.loads(r.read())
content = page["content"]["rendered"]

# Fix truncated anchor text
old = "MD&amp;A &#8211; September 30, 202<"
new = "MD&amp;A &#8211; September 30, 2024<"

# Also try plain version
old2 = "> MD&amp;A &#8211; September 30, 202<"
new2 = "> MD&amp;A &#8211; September 30, 2024<"

count1 = content.count("September 30, 202<")
count2 = content.count("September 30, 202 ")
print(f"Occurrences 'September 30, 202<': {count1}")
print(f"Occurrences 'September 30, 202 ': {count2}")

fixed = content.replace("September 30, 202<", "September 30, 2024<")
fixed = fixed.replace("September 30, 202 ", "September 30, 2024 ")

changed = content != fixed
print(f"Content changed: {changed}")

if changed:
    payload = json.dumps({"content": fixed}).encode()
    req2 = urllib.request.Request(f"{base}/pages/5686", data=payload, headers=hdrs, method="POST")
    with urllib.request.urlopen(req2) as r:
        result = json.loads(r.read())
    print(f"Updated! Length: {len(result['content']['rendered'])}")
else:
    print("Nothing to fix - checking raw content...")
    # Show context around the truncated text
    idx = content.find("September 30, 202")
    while idx != -1:
        print(f"  ...{repr(content[idx-10:idx+30])}...")
        idx = content.find("September 30, 202", idx+1)
