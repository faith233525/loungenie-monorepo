#!/usr/bin/env python3
"""Safe helper to build/apply WP REST POST for a template-part artifact.
Usage:
  python scripts/wp_import_template_part.py artifacts/lg9_header_template_part.json --dry-run
  python scripts/wp_import_template_part.py artifacts/lg9_header_template_part.json --apply

Environment variables used (when --apply): WP_URL, WP_USER, WP_APP_PASS
"""
import argparse, json, os, shlex, sys, subprocess

parser = argparse.ArgumentParser()
parser.add_argument('artifact', help='path to template-part JSON artifact')
parser.add_argument('--apply', action='store_true', help='actually POST to WP REST (requires env creds)')
args = parser.parse_args()

with open(args.artifact, 'r', encoding='utf-8') as f:
    payload = json.load(f)

# Keep payload as-is; REST endpoint for template parts may vary by WP version/plugin.
# We'll compose a curl command the operator can review.
wp_url = os.environ.get('WP_URL', 'https://example.com')
endpoint = f"{wp_url.rstrip('/')}/wp-json/wp/v2/template-parts"

data_json = json.dumps(payload, ensure_ascii=False)
# Write a temp file for curl to read safely
import tempfile
with tempfile.NamedTemporaryFile('w', delete=False, encoding='utf-8', suffix='.json') as tmp:
    tmp.write(data_json)
    tmp_path = tmp.name

curl_cmd = [
    'curl', '-sS', '-X', 'POST', endpoint,
    '-H', 'Content-Type: application/json',
    '--data-binary', f"@{tmp_path}",
    '-u', 'WP_USER:WP_APP_PASS'
]

print('# Dry-run: safe curl command to create/import template-part (inspect before running)')
print(' '.join(shlex.quote(p) for p in curl_cmd))
print('\n# You can replace WP_USER and WP_APP_PASS with your credentials or set environment variables and use the --apply flag to post directly from this script.')

if args.apply:
    wp_user = os.environ.get('WP_USER')
    wp_app = os.environ.get('WP_APP_PASS')
    if not (wp_user and wp_app and os.environ.get('WP_URL')):
        print('Missing WP_URL, WP_USER, or WP_APP_PASS in environment; aborting.')
        sys.exit(1)
    env = os.environ.copy()
    cmd = [
        'curl', '-sS', '-X', 'POST', endpoint,
        '-H', 'Content-Type: application/json',
        '--data-binary', f"@{tmp_path}",
        '-u', f"{wp_user}:{wp_app}"
    ]
    print('Posting to', endpoint)
    p = subprocess.run(cmd, env=env)
    if p.returncode == 0:
        print('POST completed (check response).')
    else:
        print('POST failed, exit code', p.returncode)

# leave temp file for debugging
print('\n# Temp payload file left at:', tmp_path)