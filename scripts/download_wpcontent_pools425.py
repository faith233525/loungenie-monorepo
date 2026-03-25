import ftplib, os, hashlib, sys
HOST='ftp.poolsafeinc.com'
USER='pools425'
PORT=21
OUT='artifacts/wp-content-pools425-attempt.tar.gz'
paths=['/loungenie.com/staging/wp-content.tar.gz','/staging/wp-content.tar.gz','/staging/wp-content/wp-content.tar.gz']
passwords=['','LounGenie21!']

os.makedirs('artifacts',exist_ok=True)

def sha256(path):
    import hashlib
    h=hashlib.sha256()
    with open(path,'rb') as f:
        for chunk in iter(lambda: f.read(8192),b''):
            h.update(chunk)
    return h.hexdigest()

for pwd in passwords:
    for p in paths:
        print(f"Trying user={USER!r} pwd={'(empty)' if pwd=='' else '*****'} path={p}")
        try:
            ftps=ftplib.FTP_TLS()
            ftps.connect(HOST,PORT,timeout=30)
            ftps.login(USER,pwd)
            ftps.prot_p()
            with open(OUT,'wb') as f:
                ftps.retrbinary('RETR '+p,f.write)
            ftps.quit()
            size=os.path.getsize(OUT)
            print('SUCCESS FTPS',OUT,size,sha256(OUT))
            sys.exit(0)
        except ftplib.error_perm as e:
            print('FTPS perm error',e)
        except Exception as e:
            print('FTPS error',e)
        try:
            ftp=ftplib.FTP()
            ftp.connect(HOST,PORT,timeout=30)
            ftp.login(USER,pwd)
            with open(OUT,'wb') as f:
                ftp.retrbinary('RETR '+p,f.write)
            ftp.quit()
            size=os.path.getsize(OUT)
            print('SUCCESS FTP',OUT,size,sha256(OUT))
            sys.exit(0)
        except Exception as e:
            print('FTP error',e)
print('All attempts failed')
sys.exit(2)
