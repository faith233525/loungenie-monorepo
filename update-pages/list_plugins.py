from ftplib import FTP
import os
host='ftp.poolsafeinc.com'
user='copilot@loungenie.com'
passwd=os.environ.get('FTP_PASSWORD')
paths=[
    '/home/pools425/loungenie.com/loungenie/wp-content/plugins',
    '/home/pools425/loungenie.com/wp-content/plugins',
    'wp-content/plugins',
    'loungenie/wp-content/plugins',
    './wp-content/plugins',
]
ftp=FTP(host,timeout=30)
ftp.login(user,passwd)
print('FTP initial pwd:', ftp.pwd())
found=None
for p in paths:
    try:
        ftp.cwd(p)
        print('Changed to',p)
        found=p
        break
    except Exception as e:
        print('CWD',p,'failed:',e)
if not found:
    print('No plugin path found from probe list.')
    ftp.quit()
    raise SystemExit(1)
items=ftp.nlst()
print('Plugin items:')
for it in items:
    print(it)
ftp.quit()
