import ftplib
import os
import sys
import time


def download_ftps(host, user, passwd, remote_path, out_path, timeout=60):
    os.makedirs(os.path.dirname(out_path) or '.', exist_ok=True)
    ftp = ftplib.FTP_TLS()
    ftp.connect(host, 21, timeout=timeout)
    ftp.set_pasv(True)
    ftp.login(user, passwd)
    ftp.prot_p()

    # Try to get remote file size
    try:
        remote_size = ftp.size(remote_path)
    except Exception:
        # try cwd then size
        dirname, fname = os.path.split(remote_path)
        if dirname:
            ftp.cwd(dirname)
        remote_size = ftp.size(fname)
        remote_path = fname

    local_size = 0
    if os.path.exists(out_path):
        local_size = os.path.getsize(out_path)

    if local_size >= (remote_size or 0) and remote_size not in (None, 0):
        print(f"Local file already complete ({local_size} bytes). Skipping download.")
        ftp.quit()
        return

    # Open file for append (resume)
    mode = 'ab' if local_size else 'wb'
    with open(out_path, mode) as f:
        downloaded = local_size

        def callback(data):
            nonlocal downloaded
            f.write(data)
            downloaded += len(data)

        rest = local_size if local_size else None
        cmd = f'RETR {remote_path}'

        print(f"Starting download: {remote_path} -> {out_path}")
        start = time.time()
        try:
            if rest:
                ftp.retrbinary(cmd, callback, blocksize=32768, rest=rest)
            else:
                ftp.retrbinary(cmd, callback, blocksize=32768)
        except Exception as e:
            print('Download error:', e)
            ftp.quit()
            raise

        elapsed = time.time() - start
        print(f"Finished. {downloaded} bytes in {elapsed:.1f}s")

    ftp.quit()


def main():
    host = os.environ.get('FTP_HOST', 'ftp.poolsafeinc.com')
    user = os.environ.get('FTP_USER')
    passwd = os.environ.get('FTP_PASS')
    remote = os.environ.get('REMOTE_PATH', '/loungenie.com/staging/wp-content.tar.gz')
    out = os.environ.get('OUT_PATH', 'artifacts/wp-content-ftps.tar.gz')

    if not user or not passwd:
        print('FTP_USER and FTP_PASS environment variables required')
        sys.exit(2)

    try:
        download_ftps(host, user, passwd, remote, out)
    except Exception as e:
        print('Failed:', e)
        sys.exit(1)


if __name__ == '__main__':
    main()
import ftplib
import hashlib
import os
import sys

HOST = 'ftp.poolsafeinc.com'
USER = 'copilot@loungenie.com'
PASS = 'LounGenie21!'
PORT = 21

OUT_DIR = os.path.join('artifacts')
os.makedirs(OUT_DIR, exist_ok=True)

paths = [
    '/loungenie.com/staging/wp-content.tar.gz',
    '/loungenie.com/staging/wp-content/wp-content.tar.gz',
    '/staging/wp-content.tar.gz',
    '/staging/wp-content/wp-content.tar.gz',
    '/home/pools425/loungenie.com/staging/wp-content.tar.gz',
    '/home/pools425/loungenie.com/staging/wp-content/wp-content.tar.gz',
    '/loungenie.com/staging/wp-content',
]

def sha256_file(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()

def try_download(remote_path, out_path):
    # First attempt: explicit FTPS
    try:
        ftps = ftplib.FTP_TLS()
        ftps.connect(HOST, PORT, timeout=30)
        ftps.login(USER, PASS)
        ftps.prot_p()
        with open(out_path, 'wb') as f:
            def cb(data):
                f.write(data)
            ftps.retrbinary('RETR ' + remote_path, cb)
        ftps.quit()
        return True, None
    except ftplib.error_perm as e:
        err = str(e)
        # If authentication failed (530), try plain FTP fallback
        if '530' in err:
            try:
                ftp = ftplib.FTP()
                ftp.connect(HOST, PORT, timeout=30)
                ftp.login(USER, PASS)
                with open(out_path, 'wb') as f:
                    def cb2(data):
                        f.write(data)
                    ftp.retrbinary('RETR ' + remote_path, cb2)
                ftp.quit()
                return True, None
            except Exception as e2:
                return False, f'FTPS failed ({err}); FTP fallback failed ({e2})'
        return False, err
    except Exception as e:
        return False, str(e)

def main():
    for i, p in enumerate(paths, start=1):
        # sanitize filename for attempt
        out_name = f'wp-content-ftps-attempt-{i}.tar.gz'
        out_path = os.path.join(OUT_DIR, out_name)
        print(f'Trying: {p} -> {out_path}')
        ok, err = try_download(p, out_path)
        if ok:
            size = os.path.getsize(out_path)
            sha = sha256_file(out_path)
            print(f'SUCCESS: downloaded to {out_path}\nSize: {size} bytes\nSHA256: {sha}')
            return 0
        else:
            print(f'Failed: {err}')
    print('All attempts failed')
    return 2

if __name__ == '__main__':
    sys.exit(main())
