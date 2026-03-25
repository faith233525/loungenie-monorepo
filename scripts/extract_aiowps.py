#!/usr/bin/env python3
"""Compute SHA256 of artifacts/aiowps_backups.tar.gz, extract it to artifacts/uploads,
and print a sample list of extracted files.
"""
import hashlib
import os
import sys
import tarfile

ROOT = os.path.dirname(os.path.dirname(__file__))
ARTIFACT = os.path.join(ROOT, 'artifacts', 'aiowps_backups.tar.gz')
OUT_DIR = os.path.join(ROOT, 'artifacts', 'uploads')

def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()

def main():
    if not os.path.exists(ARTIFACT):
        print('File not found:', ARTIFACT)
        return 2
    size = os.path.getsize(ARTIFACT)
    print('Path:', ARTIFACT)
    print('Size:', size, 'bytes')
    print('SHA256:', sha256(ARTIFACT))

    if size == 0:
        print('Archive is empty (0 bytes); nothing to extract.')
        return 3

    os.makedirs(OUT_DIR, exist_ok=True)
    try:
        with tarfile.open(ARTIFACT, 'r:gz') as t:
            members = t.getmembers()
            print('Archive contains', len(members), 'entries')
            # extract safely
            def is_within_directory(directory, target):
                abs_directory = os.path.abspath(directory)
                abs_target = os.path.abspath(target)
                return os.path.commonpath([abs_directory]) == os.path.commonpath([abs_directory, abs_target])

            for m in members:
                target_path = os.path.join(OUT_DIR, m.name)
                if not is_within_directory(OUT_DIR, target_path):
                    print('Skipping unsafe member:', m.name)
                    continue
            t.extractall(path=OUT_DIR)
    except tarfile.ReadError as e:
        print('Tar read error:', e)
        return 4
    except Exception as e:
        print('Extraction error:', e)
        return 5

    print('--- Extracted sample files ---')
    count = 0
    for root, dirs, files in os.walk(OUT_DIR):
        for fn in files:
            fp = os.path.join(root, fn)
            print(fp.replace('\\', '/'), '|', os.path.getsize(fp), 'bytes')
            count += 1
            if count >= 40:
                break
        if count >= 40:
            break

    return 0

if __name__ == '__main__':
    sys.exit(main())
