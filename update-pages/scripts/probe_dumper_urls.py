import requests
urls=[
 'https://loungenie.com/staging/db_dump.php',
 'https://loungenie.com/db_dump.php',
 'https://staging.loungenie.com/db_dump.php',
 'https://staging.loungenie.com/staging/db_dump.php',
 'http://loungenie.com/staging/db_dump.php',
 'http://staging.loungenie.com/db_dump.php'
]
res={}
for u in urls:
    try:
        r=requests.get(u,timeout=20)
        res[u]=r.status_code
    except Exception as e:
        res[u]=str(e)

import json, os
os.makedirs('artifacts',exist_ok=True)
with open('artifacts/dumper_url_probes.json','w') as f:
    json.dump(res,f,indent=2)
print('Wrote artifacts/dumper_url_probes.json')
