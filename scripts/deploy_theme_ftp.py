#!/usr/bin/env python3
"""Upload local files/dirs to FTP remote paths (safe helper for GitHub Actions).

This uploader tries passive then active mode, retries transient failures,
and reports failures. It reads FTP creds from environment variables:
  `FTP_HOST`, `FTP_USER`, `FTP_PASS`, optional `FTP_PORT`.

Example:
  python3 scripts/deploy_theme_ftp.py -m "wp-content/themes/loungenie:/public_html/stage/wp-content/themes/loungenie" -m "scripts/tmp/activate-loungenie.php:/public_html/stage/wp-content/mu-plugins/activate-loungenie.php"

The script intentionally does NOT delete remote files; run a controlled cleanup later if needed.
"""
import os
import sys
import argparse
import ftplib
import time


def norm_remote_path(p):
    return p.replace('\\', '/').rstrip('/') if p else p


def ensure_remote_dir(ftp, remote_dir):
    remote_dir = norm_remote_path(remote_dir).lstrip('/')
    if not remote_dir:
        return
    parts = remote_dir.split('/')
    cur = ''
    for part in parts:
        if not part:
            continue
        if cur == '':
            cand = '/' + part
        else:
            cand = cur.rstrip('/') + '/' + part
        try:
            ftp.mkd(cand)
            print('Created remote dir:', cand)
        except Exception:
            pass
        cur = cand


def upload_file(ftp, local_file, remote_file):
    remote_file = norm_remote_path(remote_file)
    remote_dir = os.path.dirname(remote_file)
    ensure_remote_dir(ftp, remote_dir)
    with open(local_file, 'rb') as f:
        ftp.storbinary('STOR ' + remote_file, f)


def upload_dir(ftp, local_dir, remote_dir):
    local_dir = os.path.normpath(local_dir)
    if not os.path.isdir(local_dir):
        raise FileNotFoundError(local_dir)
    for root, _, files in os.walk(local_dir):
        rel = os.path.relpath(root, local_dir)
        if rel == '.':
            rroot = remote_dir
        else:
            rroot = remote_dir.rstrip('/') + '/' + rel.replace('\\', '/')
        ensure_remote_dir(ftp, rroot)
        for fname in files:
            local_path = os.path.join(root, fname)
            remote_path = rroot.rstrip('/') + '/' + fname
            print('Uploading:', local_path, '->', remote_path)
            attempt = 0
            while attempt < 3:
                try:
                    upload_file(ftp, local_path, remote_path)
                    print('Uploaded:', local_path)
                    break
                except Exception as e:
                    attempt += 1
                    print('Upload failed:', local_path, e, 'retry', attempt)
                    time.sleep(1)
                    if attempt >= 3:
                        raise


def connect_and_upload(host, user, passwd, port, mappings, passive, timeout, retries):
    last_exc = None
    for attempt_num in range(1, retries + 1):
        ftp = None
        try:
            print(f"Connecting to FTP {host}:{port} (passive={passive}) attempt {attempt_num}/{retries}")
            ftp = ftplib.FTP()
            ftp.connect(host, port, timeout=timeout)
            ftp.login(user, passwd)
            ftp.set_pasv(passive)
            print('Connected.')
            for local, remote in mappings:
                local = os.path.normpath(local)
                remote = norm_remote_path(remote)
                if os.path.isdir(local):
                    upload_dir(ftp, local, remote)
                elif os.path.isfile(local):
                    print('Uploading single file:', local, '->', remote)
                    upload_file(ftp, local, remote)
                else:
                    print('Local path missing, skipping:', local)
            try:
                ftp.quit()
            except Exception:
                pass
            return True
        except Exception as e:
            last_exc = e
            print('Error during upload attempt:', e)
            try:
                if ftp:
                    ftp.close()
            except Exception:
                pass
            time.sleep(1)
    print('All attempts failed:', last_exc)
    return False


def parse_mapping(s):
    if ':' not in s:
        raise ValueError('Mapping must be local:remote')
    return s.split(':', 1)


def main():
    parser = argparse.ArgumentParser(description='Upload local files/dirs to FTP remote paths')
    parser.add_argument('-m', '--mapping', action='append', required=True, help='local:remote mapping')
    parser.add_argument('--port', type=int, default=int(os.environ.get('FTP_PORT', 21)))
    parser.add_argument('--passive', choices=['auto', 'true', 'false'], default='auto')
    parser.add_argument('--retries', type=int, default=3)
    parser.add_argument('--timeout', type=int, default=30)
    args = parser.parse_args()

    host = os.environ.get('FTP_HOST')
    user = os.environ.get('FTP_USER')
    passwd = os.environ.get('FTP_PASS')
    if not (host and user and (passwd is not None)):
        print('Missing FTP credentials in environment (FTP_HOST/FTP_USER/FTP_PASS).')
        sys.exit(2)

    mappings = [parse_mapping(m) for m in args.mapping]
    passives = [True, False] if args.passive == 'auto' else ([True] if args.passive == 'true' else [False])
    success = False
    for passive in passives:
        try:
            success = connect_and_upload(host, user, passwd, args.port, mappings, passive, args.timeout, args.retries)
            if success:
                break
        except Exception as e:
            print('Unexpected error for passive', passive, e)
    if not success:
        print('Upload failed.')
        sys.exit(1)
    print('Upload completed successfully.')
    sys.exit(0)


if __name__ == '__main__':
    main()
