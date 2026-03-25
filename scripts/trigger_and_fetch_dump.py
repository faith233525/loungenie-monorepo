import requests
import time
import os

ART='artifacts'
os.makedirs(ART, exist_ok=True)
URL = 'https://loungenie.com/staging/db_dump.php'

try:
    r = requests.get(URL, timeout=300)
    with open(os.path.join(ART,'db_dumper_trigger_raw.html'),'w',encoding='utf-8') as f:
        f.write(r.text)
    print('HTTP', r.status_code)
    if r.status_code == 200:
        try:
            j = r.json()
        except Exception as e:
            print('Not JSON:', e)
            j = None
        with open(os.path.join(ART,'db_dumper_trigger_response.json'),'w',encoding='utf-8') as f:
            import json
            json.dump({'status_code':r.status_code,'json':j}, f, indent=2)
        if j and j.get('status')=='ok' and j.get('file'):
            fileurl = 'https://loungenie.com/staging/wp-content/uploads/' + j.get('file')
            print('Attempting download at', fileurl)
            dr = requests.get(fileurl, timeout=300)
            print('DL status', dr.status_code)
            if dr.status_code == 200:
                outpath = os.path.join(ART, j.get('file'))
                with open(outpath,'wb') as wf:
                    wf.write(dr.content)
                print('Saved dump to', outpath)
            else:
                print('Dump file not found at', fileurl)
    else:
        print('Non-200 response; saved body to artifacts/db_dumper_trigger_raw.html')
except Exception as e:
    print('Trigger error', e)

print('Done')
