import ftplib
import os
import hashlib
import sys

# Settings requested by user
HOST = '66.102.133.37'
USER = 'fabdi21@poolsafeinc.com'
PASS = 'LounGenie21!'
PORT = 21

OUTDIR = 'artifacts'
os.makedirs(OUTDIR, exist_ok=True)

# User asked to target /://loungenie.com; try that pattern first, then the plain path
candidate_paths = [
    '/://loungenie.com/staging/wp-content.tar.gz',
    '/loungenie.com/staging/wp-content.tar.gz',
]

OUT_FILENAME = os.path.join(OUTDIR, 'wp-content-fabdi21.tar.gz')

def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()

def try_ftps(remote_path, out_path):
    try:
        ftps = ftplib.FTP_TLS()
        ftps.connect(HOST, PORT, timeout=30)
        ftps.login(USER, PASS)
        ftps.prot_p()
        ftps.set_pasv(True)
        with open(out_path, 'wb') as f:
            ftps.retrbinary('RETR ' + remote_path, f.write)
        ftps.quit()
        return True, None
    except ftplib.error_perm as e:
        return False, f'perm: {e}'
    except Exception as e:
        return False, str(e)

def try_ftp(remote_path, out_path):
    try:
        ftp = ftplib.FTP()
        ftp.connect(HOST, PORT, timeout=30)
        ftp.login(USER, PASS)
        ftp.set_pasv(True)
        with open(out_path, 'wb') as f:
            ftp.retrbinary('RETR ' + remote_path, f.write)
        ftp.quit()
        return True, None
    except Exception as e:
        return False, str(e)

def main():
    for p in candidate_paths:
        print(f'Trying FTPS -> {p}')
        ok, err = try_ftps(p, OUT_FILENAME)
        if ok:
            size = os.path.getsize(OUT_FILENAME)
            print(f'SUCCESS FTPS downloaded {OUT_FILENAME} ({size} bytes)')
            print('SHA256:', sha256(OUT_FILENAME))
            return 0
        print('FTPS failed:', err)
        print('Trying plain FTP fallback for same path')
        ok2, err2 = try_ftp(p, OUT_FILENAME)
        if ok2:
            size = os.path.getsize(OUT_FILENAME)
            print(f'SUCCESS FTP downloaded {OUT_FILENAME} ({size} bytes)')
            print('SHA256:', sha256(OUT_FILENAME))
            return 0
        print('FTP fallback failed:', err2)

    print('All attempts failed')
    return 2

if __name__ == '__main__':
    sys.exit(main())
