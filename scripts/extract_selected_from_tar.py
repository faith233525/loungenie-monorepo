import argparse
import os
import sys
import tarfile
import shutil


def is_safe_path(base_dir, target_path):
    base = os.path.abspath(base_dir)
    target = os.path.abspath(target_path)
    return target == base or target.startswith(base + os.sep)


def extract_member(tar, member, outdir):
    target = os.path.join(outdir, member.name)
    target = os.path.normpath(target)
    if not is_safe_path(outdir, target):
        raise Exception(f'Unsafe path detected: {member.name}')

    if member.isdir():
        os.makedirs(target, exist_ok=True)
        return True

    if member.issym() or member.islnk():
        return True

    parent = os.path.dirname(target)
    if parent:
        os.makedirs(parent, exist_ok=True)

    try:
        if os.path.exists(target) and os.path.getsize(target) == member.size:
            return True
    except Exception:
        pass

    f = tar.extractfile(member)
    if f is None:
        return True

    tmp = target + '.part'
    with open(tmp, 'wb') as out_f:
        shutil.copyfileobj(f, out_f, length=1024*1024)
    os.replace(tmp, target)
    return True


def extract_selected(tar_path, outdir, prefixes):
    prefixes = [p.rstrip('/') + '/' if not p.endswith('/') else p for p in prefixes]
    os.makedirs(outdir, exist_ok=True)
    with tarfile.open(tar_path, 'r:*') as tar:
        members = tar.getmembers()
        total = 0
        for m in members:
            name = m.name
            for p in prefixes:
                if name == p.rstrip('/') or name.startswith(p):
                    extract_member(tar, m, outdir)
                    total += 1
                    break
        print(f'Extracted {total} members matching prefixes: {prefixes}')


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--file', required=True)
    p.add_argument('--out', required=True)
    p.add_argument('--prefix', action='append', required=True, help='Prefix to extract (can repeat)')
    args = p.parse_args()

    if not os.path.exists(args.file):
        print('Archive not found:', args.file)
        sys.exit(2)

    try:
        extract_selected(args.file, args.out, args.prefix)
    except Exception as e:
        print('Extraction failed:', e, file=sys.stderr)
        sys.exit(1)

    print('Selected extraction complete')


if __name__ == '__main__':
    main()
