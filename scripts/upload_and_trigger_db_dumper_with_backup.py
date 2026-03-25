import ftplib
import ssl
import requests
import time
import os

# FTPS credentials (user-provided)
FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'

# Target remote base
CANDIDATES = [
    '/loungenie.com/public_html/staging',
    '/public_html/staging',
    '/staging',
]

LOCAL_PHP = 'scripts/db_dumper.php'
REMOTE_NAME = 'db_dump.php'

STAGING_URLS = [
    'https://loungenie.com/staging/',
]

ARTIFACTS_DIR = 'artifacts'
os.makedirs(ARTIFACTS_DIR, exist_ok=True)

success = False
used_remote_path = None
ftp = None

for base in CANDIDATES:
    try:
        print('Trying candidate base:', base)
        ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=60)
        ftps.login(FTPS_USER, FTPS_PASS)
        ftps.prot_p()
        try:
            ftps.cwd(base)
        except Exception as e:
            print('cwd failed for', base, '->', e)
            # try creating the path
            try:
                parts = [p for p in base.split('/') if p]
                cur = ''
                for p in parts:
                    cur = cur + '/' + p
                    try:
                        ftps.cwd(cur)
                    except:
                        try:
                            ftps.mkd(cur)
                            ftps.cwd(cur)
                        except Exception as ex2:
                            print('mkdir/cwd failed for', cur, ex2)
                            raise
            except Exception as e2:
                print('Failed to ensure path', base, e2)
                try:
                    ftps.quit()
                except:
                    pass
                continue
        # upload file
        with open(LOCAL_PHP, 'rb') as f:
            print('Uploading', LOCAL_PHP, 'to', base + '/' + REMOTE_NAME)
            ftps.storbinary('STOR ' + REMOTE_NAME, f)
        used_remote_path = (base, REMOTE_NAME)
        ftp = ftps
        success = True
        break
    except Exception as e:
        print('Candidate failed:', base, 'error:', e)
        try:
            ftps.quit()
        except:
            pass
        continue

if not success:
    print('Failed to upload dumper to any candidate path.')
    exit(2)

# Attempt to set permissive perms if possible
try:
    ftp.sendcmd('SITE CHMOD 0644 ' + os.path.join(used_remote_path[0], used_remote_path[1]))
except Exception as e:
    print('CHMOD failed (non-fatal):', e)

# Trigger via HTTP on staging URL
triggered = False
dump_info = None
for base_url in STAGING_URLS:
    try:
        url = base_url.rstrip('/') + '/' + REMOTE_NAME
        print('Attempting HTTP trigger at', url)
        r = requests.get(url, timeout=120, verify=True)
        print('HTTP status:', r.status_code)
        if r.status_code == 200:
            try:
                j = r.json()
                print('JSON response:', j)
                dump_info = j
                triggered = True
                if j.get('status') == 'ok' and j.get('file'):
                    fileurl = base_url.rstrip('/') + '/wp-content/uploads/' + j.get('file')
                    print('Attempting download from', fileurl)
                    dr = requests.get(fileurl, timeout=120, verify=True)
                    if dr.status_code == 200:
                        outpath = os.path.join(ARTIFACTS_DIR, j.get('file'))
                        with open(outpath, 'wb') as f:
                            f.write(dr.content)
                        print('Downloaded dump to', outpath)
                        print('SUCCESS')
                        break
            except Exception as ex:
                print('Failed parsing JSON or downloading:', ex)
        else:
            print('Non-200 response, body (first 500 chars):', r.text[:500])
    except Exception as e:
        print('HTTP trigger failed for', base_url, e)

# Save a record of where we uploaded
with open(os.path.join(ARTIFACTS_DIR, 'db_dumper_upload_record_backup.txt'), 'w') as f:
    f.write('uploaded_to: %s\n' % (str(used_remote_path)))
    f.write('trigger_response: %s\n' % (str(dump_info)))

print('Done.')
