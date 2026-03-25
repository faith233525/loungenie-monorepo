import ftplib
import requests
import os

# Backup FTPS credentials provided by user
FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'

LOCAL_PHP = 'scripts/db_dumper.php'
REMOTE_DIR = '/home/pools425/loungenie.com/staging'
REMOTE_NAME = 'db_dump.php'

ARTIFACTS_DIR = 'artifacts'
os.makedirs(ARTIFACTS_DIR, exist_ok=True)

# Upload via FTPS
try:
    ftps = ftplib.FTP_TLS(FTPS_HOST, timeout=60)
    ftps.login(FTPS_USER, FTPS_PASS)
    ftps.prot_p()
    ftps.cwd(REMOTE_DIR)
    with open(LOCAL_PHP, 'rb') as f:
        ftps.storbinary('STOR ' + REMOTE_NAME, f)
    ftps.quit()
    uploaded = True
    upload_record = {'uploaded': True, 'remote': REMOTE_DIR + '/' + REMOTE_NAME}
    print('Uploaded dumper to', REMOTE_DIR)
except Exception as e:
    uploaded = False
    upload_record = {'uploaded': False, 'error': str(e)}
    print('Upload failed:', e)

with open(os.path.join(ARTIFACTS_DIR, 'db_dumper_upload_record_backup.json'), 'w') as f:
    import json
    json.dump(upload_record, f, indent=2)

# Trigger via HTTP
if uploaded:
    try:
        url = 'https://loungenie.com/staging/' + REMOTE_NAME
        print('Triggering dumper at', url)
        r = requests.get(url, timeout=300)
        print('HTTP status', r.status_code)
        if r.status_code == 200:
            try:
                j = r.json()
                with open(os.path.join(ARTIFACTS_DIR, 'db_dumper_trigger_response_backup.json'), 'w') as f:
                    json.dump(j, f, indent=2)
                if j.get('status') == 'ok' and j.get('file'):
                    fileurl = 'https://loungenie.com/staging/wp-content/uploads/' + j.get('file')
                    dr = requests.get(fileurl, timeout=300)
                    if dr.status_code == 200:
                        outpath = os.path.join(ARTIFACTS_DIR, j.get('file'))
                        with open(outpath, 'wb') as wf:
                            wf.write(dr.content)
                        print('Downloaded dump to', outpath)
                        result = {'downloaded': True, 'file': outpath}
                        with open(os.path.join(ARTIFACTS_DIR, 'db_dumper_result_backup.json'), 'w') as f:
                            json.dump(result, f, indent=2)
                    else:
                        print('Failed to download dump file, status', dr.status_code)
                else:
                    print('Dumper ran but did not return file info:', j)
            except Exception as e:
                print('Error parsing response or downloading:', e)
        else:
            print('Non-200 response when triggering dumper')
    except Exception as e:
        print('HTTP trigger failed:', e)

print('Script finished.')
