#!/usr/bin/env python3
from ftplib import FTP_TLS, error_perm
FTP_HOST='ftp.poolsafeinc.com'
FTP_USER='copilot21@loungenie.com'
FTP_PASS='Quiet4Peg21!'
BASES = ['','/home/pools425/loungenie.com/loungenie','/home/pools425/public_html','/home/pools425','/public_html','/public_html/staging','/loungenie','/loungenie/staging','/']
from pathlib import Path

def try_list(path):
    try:
        ft = FTP_TLS(timeout=30)
        ft.auth()
        ft.prot_p()
        ft.connect(FTP_HOST,21,timeout=20)
        ft.login(FTP_USER, FTP_PASS)
        try:
            ft.cwd(path)
            print('CWD OK:', path)
            print('NLST:', ft.nlst()[:20])
            ft.quit()
            return True
        except error_perm as e:
            ft.quit()
            return False
        except Exception as e:
            try:
                ft.quit()
            except Exception:
                pass
            return False
    except Exception as e:
        print('conn error for', path, e)
        return False

found = False
for base in BASES:
    candidate = (base.rstrip('/') + '/wp-content/uploads').replace('//','/')
    if try_list(candidate):
        print('FOUND', candidate)
        found = True
        break
    # try without base
    if try_list('/wp-content/uploads'):
        print('FOUND', '/wp-content/uploads')
        found = True
        break

if not found:
    print('Did not locate uploads path with tested bases')
