import urllib.request, json, base64

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred, "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"

req = urllib.request.Request(f"{base}/pages/5651", headers=hdrs)
with urllib.request.urlopen(req) as r:
    page = json.loads(r.read())

content = page["content"]["rendered"]
old_url = "steven-glaser-10.jpg-238x300.webp"
new_url = "steven-glaser-10.jpg.webp"

count = content.count(old_url)
print(f"Occurrences to replace: {count}")

new_content = content.replace(old_url, new_url)

payload = json.dumps({"content": new_content}).encode()
req2 = urllib.request.Request(f"{base}/pages/5651", data=payload, headers=hdrs, method="POST")
with urllib.request.urlopen(req2) as r:
    result = json.loads(r.read())

result_len = len(result["content"]["rendered"])
print(f"Updated! Content length: {result_len}")
print("Steven Glaser now uses 600x756 full-size image instead of 238x300 thumbnail")
