import urllib.request
import json
import base64

auth = base64.b64encode(b"copilot:7NiL OZ17 ApP3 tIgF 6zlT ug7u").decode()
base = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2"
headers = {"Authorization": "Basic " + auth}

req = urllib.request.Request(base + "/menus?per_page=100", headers=headers)
menus = json.loads(urllib.request.urlopen(req, timeout=30).read())

for m in menus:
    print("\nMENU", m["id"], repr(m["name"]), "slug", m["slug"], "locations", m.get("locations"))
    req2 = urllib.request.Request(base + f"/menu-items?per_page=100&menus={m['id']}", headers=headers)
    items = json.loads(urllib.request.urlopen(req2, timeout=30).read())
    for i in items:
        print(" ", i["id"], "ord", i["menu_order"], "parent", i["parent"], "title", i["title"]["rendered"], "obj", i.get("object_id"))
