#!/usr/bin/env python3
"""
Attempt to download aiowps_backups.tar.gz using the backup@loungenie.com FTPS account.
Saves to artifacts/aiowps_backups.tar.gz and prints SHA256 if downloaded.
"""
import os
import ftplib
import hashlib
import sys

HOST = 'ftp.poolsafeinc.com'
PORT = 21
USER = 'backup@loungenie.com'
PASS = 'LounGenie21!'

DEST_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'artifacts')
os.makedirs(DEST_DIR, exist_ok=True)
LOCAL_NAME = 'aiowps_backups.tar.gz'
LOCAL_PATH = os.path.join(DEST_DIR, LOCAL_NAME)

candidate_paths = [
    '/loungenie.com/public_html/staging/wp-content/aiowps_backups.tar.gz',
    '/loungenie.com/staging/wp-content/aiowps_backups.tar.gz',
    '/home/pools425/loungenie.com/staging/wp-content/aiowps_backups.tar.gz',
    '/public_html/staging/wp-content/aiowps_backups.tar.gz',
    '/staging/wp-content/aiowps_backups.tar.gz',
    '/wp-content/aiowps_backups.tar.gz',
    '/loungenie.com/staging/aiowps_backups.tar.gz',
    '/staging/aiowps_backups.tar.gz',
    '/loungenie.com/public_html/staging/aiowps_backups.tar.gz',
    '/loungenie.com/staging/wp-content/uploads/aiowps_backups.tar.gz',
    '/loungenie.com/public_html/staging/wp-content/uploads/aiowps_backups.tar.gz',
    '/home/pools425/loungenie.com/public_html/staging/wp-content/aiowps_backups.tar.gz',
    '/home/pools425/loungenie.com/public_html/staging/aiowps_backups.tar.gz',
]

def sha256_of(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()

def try_download(remote_path):
    try:
        ftp = ftplib.FTP_TLS()
        ftp.connect(HOST, PORT, timeout=30)
        ftp.login(USER, PASS)
        ftp.prot_p()
        with open(LOCAL_PATH, 'wb') as f:
            ftp.retrbinary('RETR ' + remote_path, f.write)
        ftp.quit()
        return True, None
    except Exception as e:
        return False, e

def main():
    # remove existing
    try:
        if os.path.exists(LOCAL_PATH):
            os.remove(LOCAL_PATH)
    except Exception:
        pass

    for p in candidate_paths:
        print('Trying:', p)
        ok, err = try_download(p)
        if ok:
            print('Downloaded:', LOCAL_PATH)
            print('SHA256:', sha256_of(LOCAL_PATH))
            return 0
        else:
            print('Failed:', repr(err))

    print('All attempts failed')
    return 1

if __name__ == '__main__':
    sys.exit(main())
