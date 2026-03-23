from ftplib import FTP
import os
ftp=FTP('ftp.poolsafeinc.com',timeout=30)
ftp.login('copilot@loungenie.com',os.environ.get('FTP_PASSWORD'))
try:
    ftp.cwd('wp-content/uploads')
    items=ftp.nlst()
    print('uploads items sample:', items[:20])
    # try wpo
    try:
        ftp.cwd('wpo')
        it=ftp.nlst()
        print('wpo entries count', len(it))
        for i in it[:50]:
            s=None
            try:
                s=ftp.size(i)
            except Exception:
                s=None
            print(i, s)
    except Exception as e:
        print('wpo cwd err',e)
except Exception as e:
    print('err',e)
finally:
    try: ftp.quit()
    except: pass
