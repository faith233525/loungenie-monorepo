#!/usr/bin/env python3
"""
Simple FTPS/FTP CLI for listing, uploading, and downloading files.
Usage examples in README_FTP.md
"""
import argparse
import getpass
import os
from ftplib import FTP, FTP_TLS


def connect_ftps(host, port, user, passwd, use_ftps=True, passive=True):
    if use_ftps:
        ft = FTP_TLS()
    else:
        ft = FTP()
    ft.connect(host, port, timeout=30)
    ft.set_pasv(passive)
    ft.login(user, passwd)
    if use_ftps:
        ft.prot_p()
    return ft


def list_dir(ft, path='.'):
    try:
        files = []
        ft.retrlines(f'LIST {path}', callback=files.append)
        for line in files:
            print(line)
    except Exception as e:
        print('LIST error:', e)


def download(ft, remote_path, local_path):
    with open(local_path, 'wb') as f:
        ft.retrbinary(f'RETR {remote_path}', f.write)
    print('Downloaded', remote_path, '->', local_path)


def upload(ft, local_path, remote_path):
    with open(local_path, 'rb') as f:
        ft.storbinary(f'STOR {remote_path}', f)
    print('Uploaded', local_path, '->', remote_path)


def main():
    p = argparse.ArgumentParser(description='Simple FTP/FTPS client')
    p.add_argument('--host', required=True)
    p.add_argument('--port', type=int, help='Port (default 21 for FTP/FTPS)', default=None)
    p.add_argument('--user', required=True)
    p.add_argument('--password', help='Password (omit to prompt)')
    p.add_argument('--protocol', choices=['ftps','ftp'], default='ftps', help='Use explicit FTPS or plain FTP')
    p.add_argument('--passive', action='store_true', help='Use passive mode (default)')
    p.add_argument('--list', action='store_true', help='List remote directory')
    p.add_argument('--ls-path', default='.', help='Path to list')
    p.add_argument('--download', nargs=2, metavar=('REMOTE','LOCAL'), help='Download remote file to local path')
    p.add_argument('--upload', nargs=2, metavar=('LOCAL','REMOTE'), help='Upload local file to remote path')

    args = p.parse_args()

    port = args.port
    if port is None:
        port = 21

    passwd = args.password or os.environ.get('FTP_PASSWORD')
    if not passwd:
        passwd = getpass.getpass('FTP password: ')

    use_ftps = args.protocol == 'ftps'

    try:
        ft = connect_ftps(args.host, port, args.user, passwd, use_ftps=use_ftps, passive=args.passive)
    except Exception as e:
        print('Connection failed:', e)
        return

    try:
        if args.list:
            list_dir(ft, args.ls_path)
        if args.download:
            remote, local = args.download
            download(ft, remote, local)
        if args.upload:
            local, remote = args.upload
            upload(ft, local, remote)
    finally:
        try:
            ft.quit()
        except Exception:
            try:
                ft.close()
            except Exception:
                pass

if __name__ == '__main__':
    main()
