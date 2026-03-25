import shutil, hashlib, os, sys

src = r"C:\Users\pools\Downloads\pools425_wp872.sql"
dst_dir = r"artifacts"
os.makedirs(dst_dir, exist_ok=True)
dst = os.path.join(dst_dir, os.path.basename(src))
try:
    shutil.copy(src, dst)
except Exception as e:
    print('COPY_FAILED', e)
    sys.exit(2)
# compute sha256
h = hashlib.sha256()
with open(dst, 'rb') as f:
    for chunk in iter(lambda: f.read(8192), b''):
        h.update(chunk)
hex = h.hexdigest()
with open(os.path.join(dst_dir, os.path.basename(src)+'.sha256'), 'w') as fh:
    fh.write(hex)
print('SHA256:', hex)
print('DONE')
