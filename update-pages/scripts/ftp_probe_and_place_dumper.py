import ftplib
import os
import requests

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'copilot21@loungenie.com'
FTPS_PASS = 'Quiet4Peg21!'
LOCAL_PHP = 'scripts/db_dumper.php'
REMOTE_DIR_CANDIDATES = ['staging', 'public_html/staging', 'loungenie/staging', 'www/staging']
REMOTE_ROOT = '/'
ARTIFACTS = 'artifacts'
os.makedirs(ARTIFACTS, exist_ok=True)

ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=30)
ftps.login(FTPS_USER, FTPS_PASS)
ftps.prot_p()

# list root
print('Listing root:')
entries = []
try:
    ftps.retrlines('LIST', entries.append)
except Exception as e:
    print('LIST failed:', e)

with open(os.path.join(ARTIFACTS, 'ftp_root_list.txt'), 'w') as f:
    f.write('\n'.join(entries))

# Try to create and upload into each candidate
uploaded = False
for d in REMOTE_DIR_CANDIDATES:
    try:
        parts = d.split('/')
        # create each segment if missing
        cur = ''
        for p in parts:
            cur = (cur + '/' + p).replace('//','/')
            try:
                ftps.mkd(cur)
                print('Made dir', cur)
            except Exception as e:
                print('mkd maybe exists or failed', cur, e)
        # cwd to final
        target = '/' + d
        try:
            ftps.cwd(target)
        except Exception as e:
            print('cwd failed to', target, e)
            continue
        # upload
        with open(LOCAL_PHP, 'rb') as f:
            ftps.storbinary('STOR db_dump.php', f)
        print('Uploaded to', target + '/db_dump.php')
        uploaded = True
        remote_target = target + '/db_dump.php'
        break
    except Exception as e:
        print('Candidate failed', d, e)
        continue

ftps.quit()

# Probe likely URLs
URLS = [
    'https://loungenie.com/db_dump.php',
    'https://loungenie.com/staging/db_dump.php',
    'https://loungenie.com/loungenie/db_dump.php',
    'https://loungenie.com/staging/db_dump.php',
    'https://loungenie.com/public_html/staging/db_dump.php'
]

results = {}
for u in URLS:
    try:
        r = requests.get(u, timeout=30)
        results[u] = {'status': r.status_code, 'len': len(r.text)}
        print('Probed', u, '->', r.status_code)
    except Exception as e:
        results[u] = {'error': str(e)}
        print('Probe fail', u, e)

import json
with open(os.path.join(ARTIFACTS,'ftp_probe_results.json'),'w') as f:
    json.dump({'uploaded':uploaded, 'remote_target': locals().get('remote_target',None), 'root_list': entries, 'probes': results}, f, indent=2)

print('Done.')
