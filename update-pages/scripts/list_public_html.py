#!/usr/bin/env python3
"""List directories and files under a specific FTP path and search for staging or aiowps files."""
import ftplib
import os
import sys

HOST = 'ftp.poolsafeinc.com'
PORT = 21
USER = 'Github@loungenie.com'
PASS = 'LounGenie21!'

ROOT = '/loungenie.com/public_html'
MAX_DEPTH = 4

def connect():
    try:
        ftp = ftplib.FTP_TLS()
        ftp.connect(HOST, PORT, timeout=30)
        ftp.login(USER, PASS)
        try:
            ftp.prot_p()
        except Exception:
            pass
        return ftp
    except Exception as e:
        # fallback to plain FTP
        ftp = ftplib.FTP()
        ftp.connect(HOST, PORT, timeout=30)
        ftp.login(USER, PASS)
        return ftp

def list_dir(ftp, path):
    items = []
    try:
        # try MLSD
        try:
            for name, facts in ftp.mlsd(path):
                items.append((name, facts.get('type','file')))
            return items
        except Exception:
            names = ftp.nlst(path)
            for n in names:
                # normalize basename
                b = os.path.basename(n)
                # try to detect dir
                typ = 'file'
                try:
                    ftp.cwd(n)
                    ftp.cwd('..')
                    typ = 'dir'
                except Exception:
                    typ = 'file'
                items.append((b, typ))
            return items
    except ftplib.error_perm:
        return []

found = []

def walk(ftp, path, depth=0):
    if depth > MAX_DEPTH:
        return
    entries = list_dir(ftp, path)
    for name, typ in entries:
        full = path.rstrip('/') + '/' + name if path != '/' else '/' + name
        print(('  ' * depth) + f"- {name} ({typ})")
        if typ == 'dir':
            walk(ftp, full, depth+1)
        else:
            lname = name.lower()
            if 'staging' in full.lower() or 'aiowps' in lname or lname.endswith('.tar.gz') or lname.endswith('.zip'):
                found.append(full)

def main():
    print('Connecting to', HOST)
    ftp = connect()
    try:
        print('CWD to', ROOT)
        ftp.cwd(ROOT)
    except Exception as e:
        print('Failed to cwd to', ROOT, '-', e)
        # try without prefix
        try:
            ftp.cwd('/public_html')
            print('CWD to /public_html')
        except Exception as e2:
            print('Failed to cwd to /public_html -', e2)
    print('Listing tree under', ROOT)
    walk(ftp, ROOT, 0)
    print('\nFound matches:')
    if found:
        for f in found:
            print(f)
    else:
        print('No matches found')
    try:
        ftp.quit()
    except Exception:
        pass
    return 0

if __name__ == '__main__':
    sys.exit(main())
