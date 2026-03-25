import ftplib, os, sys, traceback

FTPS_HOST = 'ftp.poolsafeinc.com'
FTPS_USER = 'backup@loungenie.com'
FTPS_PASS = 'LounGenie21!'
START_DIR = '/loungenie.com/public_html/staging'
LOCAL_BASE = 'artifacts/uploads_backup'

os.makedirs(LOCAL_BASE, exist_ok=True)

def safe_nlst(ftp, path):
    try:
        return ftp.nlst(path)
    except Exception:
        return []


def download_dir(ftp, remote_dir, local_dir):
    os.makedirs(local_dir, exist_ok=True)
    try:
        entries = ftp.nlst(remote_dir)
    except ftplib.error_perm as e:
        # could be a file
        try:
            download_file(ftp, remote_dir, local_dir)
        except Exception:
            pass
        return
    except Exception:
        return
    for name in entries:
        if name in ('.', '..'):
            continue
        # determine if name is a dir or file by attempting cwd
        try:
            ftp.cwd(name)
            # if cwd worked, it's a dir; move back and recurse
            ftp.cwd('/')
            rel = os.path.relpath(name, remote_dir) if remote_dir != name else os.path.basename(name)
            new_remote = name
            new_local = os.path.join(local_dir, os.path.basename(name))
            download_dir(ftp, new_remote, new_local)
        except Exception:
            # assume file
            try:
                download_file(ftp, name, local_dir)
            except Exception:
                # try with full path
                try:
                    download_file(ftp, os.path.join(remote_dir, os.path.basename(name)), local_dir)
                except Exception:
                    pass


def download_file(ftp, remote_path, local_dir):
    os.makedirs(local_dir, exist_ok=True)
    local_path = os.path.join(local_dir, os.path.basename(remote_path))
    try:
        with open(local_path, 'wb') as f:
            ftp.retrbinary('RETR ' + remote_path, f.write)
        print('Downloaded', remote_path, '->', local_path)
    except Exception as e:
        print('Failed download', remote_path, e)


if __name__ == '__main__':
    try:
        ftp = ftplib.FTP_TLS(FTPS_HOST, timeout=120)
        ftp.login(FTPS_USER, FTPS_PASS)
        ftp.prot_p()
        try:
            ftp.cwd(START_DIR)
        except Exception as e:
            print('Cannot cwd to start dir:', e)
            # attempt to download from START_DIR as file
        download_dir(ftp, START_DIR, LOCAL_BASE)
        ftp.quit()
    except Exception as e:
        print('FTPS error', e)
        traceback.print_exc()
        sys.exit(1)
    print('Done')
