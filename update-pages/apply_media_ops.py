"""
apply_media_ops.py
  - Trash 30 duplicate media items
  - Update alt_text / title on 186 media items
Uses admin app password with urllib (same as professional-redesign-v12.py)
"""
import base64, csv, json, os, urllib.request, urllib.error

AUTH    = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
HEADERS = {
    "Authorization": f"Basic {AUTH}",
    "Content-Type":  "application/json",
    "User-Agent":    "Mozilla/5.0",
}
BASE    = "https://www.loungenie.com/wp-json/wp/v2/media"
DIR     = os.path.dirname(os.path.abspath(__file__))

def api_request(url, method='GET', payload=None):
    data = json.dumps(payload).encode() if payload is not None else None
    req  = urllib.request.Request(url, data=data, headers=HEADERS, method=method)
    try:
        r = urllib.request.urlopen(req, timeout=30)
        return r.getcode(), json.loads(r.read())
    except urllib.error.HTTPError as e:
        try:
            body = json.loads(e.read())
        except Exception:
            body = {}
        return e.code, body

# ── DELETE duplicates ──────────────────────────────────────────────────────────
print('=== DELETING DUPLICATES ===')
delete_ok = delete_fail = 0
delete_results = []

with open(os.path.join(DIR, 'media_duplicate_delete_plan.csv'), newline='', encoding='utf-8') as f:
    for row in csv.DictReader(f):
        att_id   = row['delete_id'].strip()
        del_file = row.get('delete_file', '').strip()
        url      = f'{BASE}/{att_id}?force=true'
        code, body = api_request(url, method='DELETE')
        if code in (200, 201):
            delete_ok += 1
            delete_results.append({'id': att_id, 'file': del_file, 'status': 'deleted'})
            print(f'  DELETED  {att_id}  {del_file}')
        else:
            delete_fail += 1
            msg = body.get('message', body.get('code', str(body)))[:80]
            delete_results.append({'id': att_id, 'file': del_file, 'status': f'fail_{code}', 'msg': msg})
            print(f'  FAIL({code})  {att_id}  {msg}')

print(f'\nDeletes: OK={delete_ok}  FAIL={delete_fail}\n')

# ── UPDATE metadata ────────────────────────────────────────────────────────────
print('=== UPDATING METADATA ===')
update_ok = update_fail = 0
update_results = []

# Track deleted IDs so we can skip them in update phase
deleted_ids = {r['id'] for r in delete_results if r['status'] == 'deleted'}

with open(os.path.join(DIR, 'media_update_plan.csv'), newline='', encoding='utf-8') as f:
    for row in csv.DictReader(f):
        att_id    = row['id'].strip()
        new_alt   = row.get('new_alt', '').strip()
        new_title = row.get('new_title', '').strip()

        if att_id in deleted_ids:
            print(f'  SKIP(deleted)  {att_id}')
            update_results.append({'id': att_id, 'status': 'skipped_deleted'})
            continue

        payload = {}
        if new_alt:
            payload['alt_text'] = new_alt
        if new_title:
            payload['title'] = new_title
        if not payload:
            update_results.append({'id': att_id, 'status': 'nothing_to_do'})
            continue

        url = f'{BASE}/{att_id}'
        code, body = api_request(url, method='POST', payload=payload)
        if code in (200, 201):
            update_ok += 1
            update_results.append({'id': att_id, 'status': 'updated'})
            print(f'  UPDATED  {att_id}')
        else:
            update_fail += 1
            msg = body.get('message', body.get('code', str(body)))[:80]
            update_results.append({'id': att_id, 'status': f'fail_{code}', 'msg': msg})
            print(f'  FAIL({code})  {att_id}  {msg}')

print(f'\nUpdates:  OK={update_ok}  FAIL={update_fail}')

# ── Summary ────────────────────────────────────────────────────────────────────
print()
print('═' * 50)
print('  FINAL SUMMARY')
print('═' * 50)
print(f'  Duplicates trashed : {delete_ok} / {delete_ok + delete_fail}')
print(f'  Metadata updated   : {update_ok} / {update_ok + update_fail}')
print('═' * 50)

out = os.path.join(DIR, 'media_ops_results.json')
with open(out, 'w', encoding='utf-8') as f:
    json.dump({'deletes': delete_results, 'updates': update_results}, f, indent=2)
print(f'\nFull results saved to: {out}')
