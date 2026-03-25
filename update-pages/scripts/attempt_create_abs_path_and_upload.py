import ftplib
import os

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'
LOCAL_PHP = 'scripts/db_dumper.php'
TARGET_ABS = '/home/pools425/loungenie.com/staging'
ART = 'artifacts/attempt_create_abs_path_and_upload.json'

res = {'attempted': TARGET_ABS, 'steps': []}

try:
    ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=60)
    ftps.login(FTPS_USER, FTPS_PASS)
    ftps.prot_p()
    # break into segments
    segs = TARGET_ABS.strip('/').split('/')
    cur = ''
    for s in segs:
        cur = cur + '/' + s
        try:
            ftps.mkd(cur)
            res['steps'].append({'seg':cur,'mkd':'ok'})
        except Exception as e:
            res['steps'].append({'seg':cur,'mkd':'fail','error':str(e)})
    # attempt cwd to full
    try:
        ftps.cwd(TARGET_ABS)
        res['cwd'] = 'ok'
        # upload
        with open(LOCAL_PHP, 'rb') as f:
            ftps.storbinary('STOR db_dump.php', f)
        res['upload'] = 'ok'
    except Exception as e:
        res['cwd'] = 'fail'
        res['cwd_error'] = str(e)
    ftps.quit()
except Exception as e:
    res['connect_error'] = str(e)

import json
os.makedirs('artifacts', exist_ok=True)
with open(ART,'w') as f:
    json.dump(res,f,indent=2)

print('Wrote', ART)
