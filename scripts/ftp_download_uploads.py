import sys
import os
from ftplib import FTP, error_perm


def is_dir(ftp, name):
    cur = ftp.pwd()
    try:
        ftp.cwd(name)
        ftp.cwd(cur)
        return True
    except Exception:
        try:
            ftp.cwd(cur + '/' + name)
            ftp.cwd(cur)
            return True
        except Exception:
            return False


def download_dir(ftp, remote_dir, local_dir):
    os.makedirs(local_dir, exist_ok=True)
    try:
        ftp.cwd(remote_dir)
    except Exception as e:
        print(f"Cannot cwd to {remote_dir}: {e}")
        return
    try:
        entries = ftp.nlst()
    except error_perm as e:
        print(f"NLST permission error in {remote_dir}: {e}")
        return
    for e in entries:
        if e in ('.', '..'):
            continue
        if is_dir(ftp, e):
            print(f"DIR: {remote_dir}/{e}")
            download_dir(ftp, remote_dir + '/' + e, os.path.join(local_dir, e))
            try:
                ftp.cwd(remote_dir)
            except Exception:
                pass
        else:
            local_path = os.path.join(local_dir, e)
            if os.path.exists(local_path):
                print(f"Skipping existing file {local_path}")
                continue
            print(f"DL: {remote_dir}/{e} -> {local_path}")
            try:
                with open(local_path, 'wb') as f:
                    ftp.retrbinary('RETR ' + e, f.write)
            except Exception as ex:
                print(f"Failed to download {remote_dir}/{e}: {ex}")


if __name__ == '__main__':
    if len(sys.argv) < 6:
        print("Usage: python ftp_download_uploads.py <host> <user> <pass> <remote_dir> <local_dir>")
        sys.exit(1)
    host, user, password, remote_dir, local_dir = sys.argv[1:6]
    ftp = FTP()
    print(f"Connecting to {host} as {user}")
    ftp.connect(host, 21, timeout=30)
    ftp.login(user, password)
    print("Connected. Starting download...")
    download_dir(ftp, remote_dir, local_dir)
    ftp.quit()
    print("Done")
