from ftplib import FTP_TLS
import sys

HOST = 'ftp.poolsafeinc.com'
USER = 'fabdi@poolsafeinc.com'
PASS = 'LounGenie21'

def main():
    try:
        ftps = FTP_TLS(HOST, timeout=30)
        ftps.set_pasv(True)
        ftps.login(USER, PASS)
        ftps.prot_p()
        print('PWD:', ftps.pwd())
        # try to change into the site staging folder
        try:
            ftps.cwd('/loungenie.com/staging')
            print('CWD to /loungenie.com/staging: OK')
            try:
                print('Listing sample:', ftps.nlst()[:10])
            except Exception as e:
                print('nlst failed:', e)
        except Exception as e:
            print('CWD to /loungenie.com/staging failed:', e)

        # download .lastlogin if available
        try:
            with open('artifacts/ftp_lastlogin.txt', 'wb') as f:
                ftps.retrbinary('RETR .lastlogin', f.write)
            print('Downloaded .lastlogin to artifacts/ftp_lastlogin.txt')
        except Exception as e:
            print('Failed to download .lastlogin:', e)

        ftps.quit()
    except Exception as e:
        print('FTPS connection failed:', e)
        sys.exit(2)

if __name__ == '__main__':
    main()
