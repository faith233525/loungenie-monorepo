#!/usr/bin/env python3
import os
import sys
import json
import subprocess
from pathlib import Path

"""
Orchestrate end-to-end staging deployment using existing scripts.
Requirements:
- WP_AUTH environment variable must be set (user:pass or base64)
- Default backup file: backups/professional-redesign-v12-live-backup-20260321-160731.json
- Processes pages defined in PAGES list and writes artifacts to backups/
- Does not deploy if any validation fails
"""

BASE_BACKUP = os.environ.get('DEFAULT_BACKUP', 'backups/professional-redesign-v12-live-backup-20260321-160731.json')
WP_AUTH = os.environ.get('WP_AUTH')
if not WP_AUTH:
    print('WP_AUTH not set; aborting')
    sys.exit(2)

# Pages registry (page_id, slug)
PAGES = [
    (4701, 'home'),
    (2989, 'features'),
    (5223, 'gallery'),
    (4862, 'about'),
    (5139, 'contact'),
    (5668, 'investors'),
    (5651, 'board'),
    (5285, 'videos'),
]

WORKDIR = Path.cwd()
BACKUPS = WORKDIR / 'backups'
CONTENT_DIR = WORKDIR / 'content'
OUTPUTS = WORKDIR / 'outputs'
for d in (BACKUPS, CONTENT_DIR, OUTPUTS):
    d.mkdir(exist_ok=True)

def run(cmd, env=None, check=True):
    print('>',' '.join(cmd))
    res = subprocess.run(cmd, env=env or os.environ, capture_output=True, text=True)
    print(res.stdout)
    if res.stderr:
        print(res.stderr)
    if check and res.returncode != 0:
        raise RuntimeError(f'Command failed: {cmd} -> exit {res.returncode}')
    return res

# Helper to split WP_AUTH into username/password if possible
def split_wp_auth(auth):
    if ':' in auth:
        user, pwd = auth.split(':',1)
        return user, pwd
    return None, None

# Ensure backup file exists
backup_file = Path(BASE_BACKUP)
if not backup_file.exists():
    print('Default backup not found:', backup_file)
    sys.exit(2)

# Prepare media lookup empty (update_pages requires a media mapping file)
media_lookup_path = BACKUPS / 'media_lookup.json'
if not media_lookup_path.exists():
    media_lookup_path.write_text(json.dumps({}, indent=2), encoding='utf-8')

# Orchestrate per page
errors = []
for pid, slug in PAGES:
    print('\n--- Processing', slug, pid, '---')
    snapshot_out = BACKUPS / f'{pid}_page_snapshot.json'
    report_out = BACKUPS / f'{pid}_snapshot_validation_report.json'
    payload_out = BACKUPS / f'{pid}_gutenberg_payload.json'

    # 1) Snapshot & validate
    try:
        run([sys.executable, 'scripts/snapshot_and_validate.py', '--page', str(pid), '--out', str(snapshot_out)])
    except Exception as e:
        print('Snapshot/validation failed for', pid, e)
        errors.append((pid, 'snapshot', str(e)))
        break

    # read report and ensure no issues
    report = json.loads(report_out.read_text(encoding='utf-8')) if report_out.exists() else {}
    if report.get('issues'):
        print('Validation issues for', pid, report.get('issues'))
        errors.append((pid, 'validation_issues', report.get('issues')))
        break

    # 2) Convert images -> attachment IDs (produces payload and mapping files)
    try:
        run([sys.executable, 'scripts/convert_images_to_attachment_ids.py', '--backup', str(backup_file), '--page', str(pid), '--out', str(payload_out)])
    except Exception as e:
        print('Conversion to attachment IDs failed for', pid, e)
        errors.append((pid, 'convert', str(e)))
        break

    # 3) Run gallery/content checks
    try:
        run([sys.executable, 'scripts/check_payload_galleries.py'])
    except Exception as e:
        print('Gallery check failed for', pid, e)
        errors.append((pid, 'gallery_check', str(e)))
        break

    # 4) Prepare content JSON for update_pages
    try:
        payload = json.loads(payload_out.read_text(encoding='utf-8'))
        content = payload.get('content', '')
        page_json = {'page_id': pid, 'content': content}
        content_file = CONTENT_DIR / f'{pid}.json'
        content_file.write_text(json.dumps(page_json, indent=2), encoding='utf-8')
        print('Wrote content file', content_file)
    except Exception as e:
        print('Failed building content file for', pid, e)
        errors.append((pid, 'build_content', str(e)))
        break

print('\n--- Orchestration complete ---')
if errors:
    print('Errors encountered:', errors)
    sys.exit(1)

# Final deployment: call update_pages.py with WP credentials derived from WP_AUTH
wp_user, wp_pass = split_wp_auth(WP_AUTH)
if not (wp_user and wp_pass):
    print('WP_AUTH not in user:pass form. Please set WP_USERNAME and WP_APP_PASSWORD in environment for deployment.')
    sys.exit(2)

os.environ['WP_USERNAME'] = wp_user
os.environ['WP_APP_PASSWORD'] = wp_pass
os.environ['WP_SITE_URL'] = 'https://www.loungenie.com/staging'

try:
    run([sys.executable, 'scripts/update_pages.py', '--content-dir', str(CONTENT_DIR), '--media', str(media_lookup_path)])
except Exception as e:
    print('Deployment failed:', e)
    sys.exit(1)

print('Deployment completed successfully')
