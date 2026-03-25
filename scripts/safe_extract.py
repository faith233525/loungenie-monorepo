import os
import tarfile
import sys
from ftplib import FTP_TLS

host = os.environ.get('FTP_HOST', 'ftp.poolsafeinc.com')
user = os.environ.get('FTP_USER', 'fabdi@poolsafeinc.com')
passwd = os.environ.get('FTP_PASS', 'LounGenie21')
remote_path = os.environ.get('REMOTE_PATH', '/loungenie.com/staging/wp-content.tar.gz')
local_archive = os.environ.get('LOCAL_ARCHIVE', 'artifacts/wp-content-ftps.tar.gz')
extract_path = os.environ.get('EXTRACT_PATH', 'artifacts/wp-content-extracted')


def download_if_needed():
    if os.path.exists(local_archive) and os.path.getsize(local_archive) > 3000000000:
        print(f"Verified: {local_archive} exists. Skipping download.")
        return

    print('Downloading via FTPS...')
    os.makedirs(os.path.dirname(local_archive) or '.', exist_ok=True)
    with FTP_TLS(host, timeout=120) as ftps:
        ftps.set_pasv(True)
        ftps.login(user, passwd)
        ftps.prot_p()
        with open(local_archive, 'wb') as f:
            ftps.retrbinary(f'RETR {remote_path}', f.write)
    print('Download complete.')


def safe_extract(tarpath, outdir):
    os.makedirs(outdir, exist_ok=True)
    with tarfile.open(tarpath, 'r:*') as tar:
        for member in tar.getmembers():
            member_path = os.path.join(outdir, member.name)
            abs_out = os.path.abspath(outdir)
            abs_target = os.path.abspath(member_path)
            if not abs_target.startswith(abs_out + os.sep) and abs_target != abs_out:
                raise Exception('Attempted Path Traversal in Tar File')
        tar.extractall(outdir)


def main():
    try:
        download_if_needed()
    except Exception as e:
        print('Download failed:', e)
        sys.exit(1)

    print(f'Extracting {local_archive} to {extract_path}...')
    try:
        safe_extract(local_archive, extract_path)
    except Exception as e:
        print('Extraction failed:', e)
        sys.exit(1)

    print('Extraction complete.')


if __name__ == '__main__':
    main()
