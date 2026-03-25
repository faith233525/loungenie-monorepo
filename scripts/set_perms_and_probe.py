import ftplib
import requests
import os

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'
REMOTE_FILE = '/home/pools425/loungenie.com/staging/db_dump.php'
URL = 'https://loungenie.com/staging/db_dump.php'
ART='artifacts'

os.makedirs(ART, exist_ok=True)
res = {}
try:
    ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=60)
    ftps.login(FTPS_USER, FTPS_PASS)
    ftps.prot_p()
    try:
        cmd = 'SITE CHMOD 0644 ' + REMOTE_FILE
        r = ftps.sendcmd(cmd)
        res['chmod'] = r
    except Exception as e:
        res['chmod_error'] = str(e)
    ftps.quit()
except Exception as e:
    res['connect_error']=str(e)

# Probe URL
try:
    r = requests.get(URL, timeout=120)
    res['http_status'] = r.status_code
    res['http_len'] = len(r.text)
    with open(os.path.join(ART,'db_dumper_probe_after_chmod.html'),'w',encoding='utf-8') as f:
        f.write(r.text)
except Exception as e:
    res['http_error'] = str(e)

import json
with open(os.path.join(ART,'set_perms_and_probe.json'),'w') as f:
    json.dump(res,f,indent=2)

print('Wrote artifacts/set_perms_and_probe.json')
