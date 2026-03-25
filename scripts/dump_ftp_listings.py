from ftplib import FTP_TLS

HOST = 'ftp.poolsafeinc.com'
USER = 'fabdi@poolsafeinc.com'
PASS = 'LounGenie21'

def save_listing(ftp, path, outpath):
    try:
        ftp.cwd(path)
    except Exception as e:
        with open(outpath, 'w') as f:
            f.write(f'Failed to CWD to {path}: {e}\n')
        return

    lines = []
    try:
        ftp.retrlines('LIST', lines.append)
    except Exception as e:
        with open(outpath, 'w') as f:
            f.write(f'LIST failed for {path}: {e}\n')
        return

    with open(outpath, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines))


def main():
    ftps = FTP_TLS(HOST, timeout=30)
    ftps.set_pasv(True)
    ftps.login(USER, PASS)
    ftps.prot_p()

    save_listing(ftps, '/loungenie.com/staging', 'artifacts/ftp_staging_list.txt')
    save_listing(ftps, '/loungenie.com', 'artifacts/ftp_loungenie_list.txt')

    ftps.quit()

if __name__ == '__main__':
    main()
