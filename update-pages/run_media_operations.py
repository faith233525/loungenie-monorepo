"""
run_media_operations.py
  1. FTP-uploads copilot_media_operations.php to wp-content/mu-plugins/
  2. Triggers https://www.loungenie.com/?copilot_run=media_ops_run_7z9k
  3. Parses JSON response and prints a summary
  4. Falls back to fetching the saved results file if trigger response isn't JSON

Requires:  FTP_PASSWORD env var (FTP_HOST / FTP_USER are optional overrides)
"""

import os
import sys
import json
import time
from ftplib import FTP_TLS
from urllib.request import urlopen, Request
from urllib.error import URLError

HOST         = os.getenv('FTP_HOST',     'ftp.poolsafeinc.com')
USER         = os.getenv('FTP_USER',     'copilot@loungenie.com')
PASS         = os.getenv('FTP_PASSWORD')
LOCAL_PLUGIN = os.path.join(os.path.dirname(__file__), 'copilot_media_operations.php')
REMOTE_DIR   = 'wp-content/mu-plugins'
REMOTE_NAME  = 'copilot_media_ops.php'
TRIGGER_URL  = 'https://www.loungenie.com/?copilot_run=media_ops_run_7z9k'
RESULTS_URL  = 'https://www.loungenie.com/wp-content/uploads/media_ops_results.json'

# ── helpers ───────────────────────────────────────────────────────────────────

def print_summary(data):
    deletes = data.get('deletes', [])
    updates  = data.get('updates', [])

    d_ok   = sum(1 for d in deletes if d.get('status') == 'deleted')
    d_miss = sum(1 for d in deletes if d.get('status') == 'not_found')

    u_alt   = sum(1 for u in updates if u.get('alt_updated'))
    u_title = sum(1 for u in updates if u.get('title_updated'))
    u_skip  = sum(1 for u in updates if u.get('status') == 'skipped_deleted')
    u_ok    = sum(1 for u in updates if u.get('status') == 'ok')

    print()
    print('═' * 50)
    print('  RESULTS SUMMARY')
    print('═' * 50)
    print(f'  Deletes  : {d_ok} deleted, {d_miss} not_found  (total {len(deletes)})')
    print(f'  Updates  : {u_ok} processed  (alt: {u_alt}, title: {u_title}, skipped_deleted: {u_skip})')
    print('═' * 50)

    # Flag any failures
    failed_deletes = [d for d in deletes if d.get('status') != 'deleted']
    if failed_deletes:
        print(f'\n  ⚠  {len(failed_deletes)} delete(s) were not_found (already gone or wrong ID):')
        for d in failed_deletes:
            print(f'     id={d["id"]}')

# ── FTP upload ────────────────────────────────────────────────────────────────

if not PASS:
    print('ERROR: FTP_PASSWORD not set in environment.')
    print('  Set it with:  $env:FTP_PASSWORD = "your-ftp-password"')
    sys.exit(1)

print(f'Connecting to {HOST} as {USER} …')
ftp = FTP_TLS()
ftp.connect(HOST, 21, timeout=30)
ftp.login(USER, PASS)
try:
    ftp.prot_p()
except Exception:
    pass
ftp.set_pasv(True)

# Navigate to mu-plugins
try:
    ftp.cwd('wp-content')
except Exception as e:
    print('ERROR: could not CWD to wp-content:', e)
    ftp.quit()
    sys.exit(1)

try:
    ftp.cwd('mu-plugins')
except Exception:
    try:
        ftp.mkd('mu-plugins')
        ftp.cwd('mu-plugins')
    except Exception as e:
        print('ERROR: could not create/enter mu-plugins:', e)
        ftp.quit()
        sys.exit(1)

with open(LOCAL_PLUGIN, 'rb') as fh:
    try:
        ftp.storbinary('STOR ' + REMOTE_NAME, fh)
        print(f'Uploaded  → {REMOTE_DIR}/{REMOTE_NAME}')
    except Exception as e:
        print('ERROR: upload failed:', e)
        ftp.quit()
        sys.exit(1)

ftp.quit()

# ── HTTP trigger ──────────────────────────────────────────────────────────────

print(f'Triggering {TRIGGER_URL} …')
results_data = None

try:
    req  = Request(TRIGGER_URL, headers={'User-Agent': 'Mozilla/5.0', 'Cache-Control': 'no-cache'})
    resp = urlopen(req, timeout=90)
    code = resp.getcode()
    body = resp.read().decode('utf-8', errors='replace')
    print(f'HTTP {code}  ({len(body)} bytes)')

    # Try to parse the JSON the plugin echoes directly
    try:
        results_data = json.loads(body)
        print('Got JSON response from plugin.')
    except json.JSONDecodeError:
        print('Response was not JSON (probably cached/redirect); will fetch results file.')
        if body.strip():
            print('Response snippet:', body[:300])

except URLError as e:
    print('Trigger request failed:', e)

# ── Fallback: fetch saved results file ───────────────────────────────────────

if results_data is None:
    print(f'\nWaiting 3 s then fetching {RESULTS_URL} …')
    time.sleep(3)
    try:
        req2  = Request(RESULTS_URL, headers={'User-Agent': 'Mozilla/5.0'})
        resp2 = urlopen(req2, timeout=30)
        results_data = json.loads(resp2.read().decode('utf-8'))
        print('Fetched results file successfully.')
    except Exception as e:
        print('Could not fetch results file:', e)

# ── Summary ───────────────────────────────────────────────────────────────────

if results_data:
    print_summary(results_data)
    # Save a local copy
    out_path = os.path.join(os.path.dirname(__file__), 'media_ops_results.json')
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(results_data, f, indent=2)
    print(f'\n  Results also saved to: {out_path}')
else:
    print('\nNo results data retrieved. Check that:')
    print('  1. FTP upload succeeded (wp-content/mu-plugins/copilot_media_ops.php was written)')
    print('  2. Site loaded without a PHP fatal error')
    print('  3. The token ?copilot_run=media_ops_run_7z9k reached PHP (not cached)')
