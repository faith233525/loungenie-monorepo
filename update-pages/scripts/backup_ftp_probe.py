import ftplib
import json

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'
ART='artifacts/backup_ftp_probe.json'

ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=30)
ftps.login(FTPS_USER, FTPS_PASS)
ftps.prot_p()

info = {}
try:
    info['pwd'] = ftps.pwd()
except Exception as e:
    info['pwd_error'] = str(e)

entries = []
try:
    ftps.retrlines('LIST', entries.append)
    info['root_list'] = entries
except Exception as e:
    info['root_list_error'] = str(e)

# try some common relative paths
candidates = ['public_html', 'public_html/staging', 'staging', 'www', 'loungenie.com/public_html/staging']
info['candidates'] = {}
for c in candidates:
    try:
        ftps.cwd(c)
        info['candidates'][c] = {'cwd_ok': True, 'pwd': ftps.pwd()}
        # list a few
        lst = []
        try:
            ftps.retrlines('LIST', lst.append)
            info['candidates'][c]['list'] = lst
        except Exception as le:
            info['candidates'][c]['list_error'] = str(le)
        ftps.cwd('/')
    except Exception as e:
        info['candidates'][c] = {'cwd_ok': False, 'error': str(e)}

with open(ART,'w') as f:
    json.dump(info, f, indent=2)

ftps.quit()
print('Wrote', ART)
