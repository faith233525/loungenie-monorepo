#!/usr/bin/env python3
"""Recursively search an FTP(FTPS) server for files matching patterns.
Saves results to artifacts/ftps_file_list.txt
"""
import ftplib
import os
import sys
import ssl

HOST = 'ftp.poolsafeinc.com'
PORT = 21
USER = 'Github@loungenie.com'
PASS = 'LounGenie21!'

OUT = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'artifacts', 'ftps_file_list.txt')
os.makedirs(os.path.dirname(OUT), exist_ok=True)

roots = ['/', '/home', '/home/pools425', '/home/pools425/loungenie.com', '/loungenie.com', '/public_html', '/public_html/staging', '/loungenie.com/public_html', '/']
matches = []

def try_connect_ftps():
    ftp = ftplib.FTP_TLS()
    ftp.connect(HOST, PORT, timeout=30)
    ftp.login(USER, PASS)
    try:
        ftp.prot_p()
    except Exception:
        pass
    return ftp

def list_dir(ftp, path):
    items = []
    try:
        # prefer MLSD
        try:
            for name, facts in ftp.mlsd(path):
                items.append((name, facts.get('type', 'file')))
            return items
        except Exception:
            names = ftp.nlst(path)
            for n in names:
                # infer type by trying to cwd
                typ = 'file'
                try:
                    ftp.cwd(n)
                    ftp.cwd('..')
                    typ = 'dir'
                except Exception:
                    typ = 'file'
                items.append((os.path.basename(n), typ))
            return items
    except ftplib.error_perm as e:
        return []

def walk(ftp, path, seen=set(), depth=0):
    if path in seen or depth > 6:
        return
    seen.add(path)
    try:
        entries = list_dir(ftp, path)
    except Exception:
        entries = []
    for name, typ in entries:
        full = path.rstrip('/') + '/' + name if path != '/' else '/' + name
        if typ == 'dir':
            walk(ftp, full, seen, depth+1)
        else:
            if name.lower().endswith('.tar.gz') or 'aiowps' in name.lower() or name.lower().endswith('.zip'):
                matches.append(full)

def main():
    print('Connecting to', HOST)
    try:
        ftp = try_connect_ftps()
    except Exception as e:
        print('FTPS connect failed:', e)
        try:
            ftp = ftplib.FTP()
            ftp.connect(HOST, PORT, timeout=30)
            ftp.login(USER, PASS)
        except Exception as e2:
            print('Plain FTP connect failed:', e2)
            return 2

    for r in roots:
        try:
            # try cwd to root
            try:
                ftp.cwd(r)
            except Exception:
                pass
            print('Searching under', r)
            walk(ftp, r, set(), 0)
        except Exception as e:
            print('Error searching', r, e)

    with open(OUT, 'w', encoding='utf-8') as f:
        if matches:
            for m in sorted(set(matches)):
                f.write(m + '\n')
            print('Matches written to', OUT)
        else:
            f.write('NO_MATCHES\n')
            print('No matches found; wrote NO_MATCHES to', OUT)

    try:
        ftp.quit()
    except Exception:
        pass
    return 0

if __name__ == '__main__':
    sys.exit(main())
