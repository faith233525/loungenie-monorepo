import ftplib
import json

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'copilot21@loungenie.com'
FTPS_PASS = 'Quiet4Peg21!'

out = {}

ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=30)
ftps.login(FTPS_USER, FTPS_PASS)
ftps.prot_p()

def safe_list(path='/'):
    try:
        items = ftps.nlst(path)
        return items
    except Exception as e:
        return {'error': str(e)}

root = safe_list('/')
out['root'] = root
# if root is list of names, iterate
if isinstance(root, list):
    for name in root:
        if name in ('.','..'):
            continue
        try:
            children = safe_list(name)
            out[name] = children
        except Exception as e:
            out[name] = {'error': str(e)}

with open('artifacts/ftp_walk.json','w') as f:
    json.dump(out, f, indent=2)

ftps.quit()
print('Wrote artifacts/ftp_walk.json')
