Simple FTPS/FTP client (Python)

This repository includes `scripts/ftp_client.py` — a minimal FTPS/FTP CLI you can run locally to connect to servers.

Examples

List remote root over explicit FTPS (port 21):

```bash
python scripts/ftp_client.py --host ftp.poolsafeinc.com --user "copilot@loungenie.com" --password "LounGenie21!" --protocol ftps --list
```

Fallback to plain FTP:

```bash
python scripts/ftp_client.py --host ftp.poolsafeinc.com --user "copilot@loungenie.com" --password "LounGenie21!" --protocol ftp --list
```

Download a file:

```bash
python scripts/ftp_client.py --host ftp.poolsafeinc.com --user copilot@loungenie.com --password "LounGenie21!" --protocol ftps --download "/home/pools425/somefile.jpg" ./somefile.jpg
```

Upload a file:

```bash
python scripts/ftp_client.py --host ftp.poolsafeinc.com --user copilot@loungenie.com --password "LounGenie21!" --protocol ftps --upload ./local.jpg "/home/pools425/public_html/uploads/local.jpg"
```

Security note

Providing passwords on the command line may expose them to other local users via process lists. Prefer setting environment variable `FTP_PASSWORD` or omitting `--password` to be prompted.
