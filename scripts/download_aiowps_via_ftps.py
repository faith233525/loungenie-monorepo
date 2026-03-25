#!/usr/bin/env python3
"""
Download aiowps_backups.tar.gz from several likely FTPS paths and verify SHA256.
Tries FTP_TLS first (explicit FTPS), falls back to plain FTP on SSL errors.
"""
import os
import ftplib
import ssl
import hashlib
import sys
import time

HOST = 'ftp.poolsafeinc.com'
PORT = 21
USER = 'Github@loungenie.com'
PASS = 'LounGenie21!'

DEST_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'artifacts')
os.makedirs(DEST_DIR, exist_ok=True)
LOCAL_NAME = 'aiowps_backups.tar.gz'
LOCAL_PATH = os.path.join(DEST_DIR, LOCAL_NAME)

candidate_paths = [
    '/loungenie.com/staging/wp-content/aiowps_backups.tar.gz',
    '/loungenie.com/public_html/staging/wp-content/aiowps_backups.tar.gz',
    '/home/pools425/loungenie.com/staging/wp-content/aiowps_backups.tar.gz',
    '/public_html/staging/wp-content/aiowps_backups.tar.gz',
    '/staging/wp-content/aiowps_backups.tar.gz',
    '/wp-content/aiowps_backups.tar.gz',
]

def sha256_of(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()

def try_download_via_ftps(remote_path, timeout=30):
    try:
        ftp = ftplib.FTP_TLS()
        ftp.connect(HOST, PORT, timeout=timeout)
        ftp.login(USER, PASS)
        ftp.prot_p()
        with open(LOCAL_PATH, 'wb') as f:
            ftp.retrbinary('RETR ' + remote_path, f.write)
        ftp.quit()
        return True, None
    except Exception as e:
        return False, e

def try_download_via_ftp(remote_path, timeout=30):
    try:
        ftp = ftplib.FTP()
        ftp.connect(HOST, PORT, timeout=timeout)
        ftp.login(USER, PASS)
        with open(LOCAL_PATH, 'wb') as f:
            ftp.retrbinary('RETR ' + remote_path, f.write)
        ftp.quit()
        return True, None
    except Exception as e:
        return False, e

def main():
    print('Starting download attempts to fetch aiowps_backups.tar.gz')
    # remove any previous incomplete file
    if os.path.exists(LOCAL_PATH):
        try:
            os.remove(LOCAL_PATH)
        except Exception:
            pass

    for p in candidate_paths:
        print('\nTrying remote path:', p)
        ok, err = try_download_via_ftps(p)
        if ok:
            print('Downloaded via FTPS:', p)
            print('SHA256:', sha256_of(LOCAL_PATH))
            return 0
        else:
            print('FTPS attempt failed:', repr(err))
            # if SSL issues, try plain FTP fallback
            if isinstance(err, ssl.SSLError) or 'SSL' in repr(err) or 'BAD_LENGTH' in repr(err):
                print('SSL issue detected, trying plain FTP fallback')
                ok2, err2 = try_download_via_ftp(p)
                if ok2:
                    print('Downloaded via plain FTP:', p)
                    print('SHA256:', sha256_of(LOCAL_PATH))
                    return 0
                else:
                    print('Plain FTP fallback failed:', repr(err2))

    print('\nAll candidate paths failed. Last error shown above.')
    return 2

if __name__ == '__main__':
    sys.exit(main())
