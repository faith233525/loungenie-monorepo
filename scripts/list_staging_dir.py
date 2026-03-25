import ftplib, json, os
FTPS_HOST='ftp.poolsafeinc.com'
FTPS_USER='backup@loungenie.com'
FTPS_PASS='LounGenie21!'
TARGET='/home/pools425/loungenie.com/staging'
ART='artifacts/list_staging_dir.json'
res={}
try:
    ftps=ftplib.FTP_TLS(FTPS_HOST, timeout=60)
    ftps.login(FTPS_USER, FTPS_PASS)
    ftps.prot_p()
    try:
        ftps.cwd(TARGET)
        lines=[]
        ftps.retrlines('LIST', lines.append)
        res['list']=lines
    except Exception as e:
        res['error']=str(e)
    ftps.quit()
except Exception as e:
    res['connect_error']=str(e)

os.makedirs('artifacts', exist_ok=True)
with open(ART,'w') as f:
    json.dump(res,f,indent=2)
print('Wrote',ART)
