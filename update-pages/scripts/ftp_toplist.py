#!/usr/bin/env python3
"""List top-level FTP entries and probe common relative paths for public_html/staging."""
import ftplib, os, sys

HOST='ftp.poolsafeinc.com'
PORT=21
USER='Github@loungenie.com'
PASS='LounGenie21!'

try:
    ftp = ftplib.FTP_TLS()
    ftp.connect(HOST, PORT, timeout=30)
    ftp.login(USER, PASS)
    try:
        ftp.prot_p()
    except Exception:
        pass
except Exception:
    ftp = ftplib.FTP()
    ftp.connect(HOST, PORT, timeout=30)
    ftp.login(USER, PASS)

def safe_list(p):
    try:
        return ftp.nlst(p)
    except Exception as e:
        return ['ERROR: ' + str(e)]

print('PWD:', ftp.pwd())
print('\nTop-level NLST:')
for item in safe_list('.'):
    print('-', item)

probes = ['loungenie.com/public_html', 'loungenie.com', 'public_html', 'staging', 'staging/wp-content', 'public_html/staging', 'loungenie.com/staging', 'home/pools425/loungenie.com/public_html']
print('\nProbing common relative paths:')
for p in probes:
    try:
        entries = ftp.nlst(p)
        print(f"\nPath: {p} -> {len(entries)} entries")
        for e in entries[:20]:
            print('  ', e)
    except Exception as e:
        print(f"Path: {p} -> ERROR: {e}")

try:
    ftp.quit()
except Exception:
    pass

sys.exit(0)
