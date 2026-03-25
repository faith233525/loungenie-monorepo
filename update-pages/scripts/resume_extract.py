import argparse
import os
import sys
import tarfile
import shutil


def is_safe_path(base_dir, target_path):
    base = os.path.abspath(base_dir)
    target = os.path.abspath(target_path)
    return target == base or target.startswith(base + os.sep)


def extract_member_stream(tar, member, outdir):
    target = os.path.join(outdir, member.name)
    target = os.path.normpath(target)
    if not is_safe_path(outdir, target):
        raise Exception(f'Unsafe path detected: {member.name}')

    if member.isdir():
        os.makedirs(target, exist_ok=True)
        return

    # Skip links for safety
    if member.issym() or member.islnk():
        return

    parent = os.path.dirname(target)
    if parent:
        os.makedirs(parent, exist_ok=True)

    # If file exists and sizes match, skip
    try:
        if os.path.exists(target) and os.path.getsize(target) == member.size:
            return
    except Exception:
        pass

    f = tar.extractfile(member)
    if f is None:
        return

    tmp_target = target + '.part'
    with open(tmp_target, 'wb') as out_f:
        shutil.copyfileobj(f, out_f, length=1024*1024)
    os.replace(tmp_target, target)


def resume_extract(tar_path, outdir):
    with tarfile.open(tar_path, 'r:*') as tar:
        members = tar.getmembers()
        total = len(members)
        print(f'Found {total} members in tar')
        for idx, m in enumerate(members, 1):
            try:
                extract_member_stream(tar, m, outdir)
            except Exception as e:
                print(f'Error extracting {m.name}: {e}', file=sys.stderr)
                raise
            if idx % 100 == 0:
                print(f'Extracted {idx}/{total} members...')


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--file', required=True)
    p.add_argument('--out', required=True)
    args = p.parse_args()

    if not os.path.exists(args.file):
        print('Archive not found:', args.file)
        sys.exit(2)

    os.makedirs(args.out, exist_ok=True)
    try:
        resume_extract(args.file, args.out)
    except Exception as e:
        print('Extraction aborted:', e, file=sys.stderr)
        sys.exit(1)

    print('Resume extraction complete')


if __name__ == '__main__':
    main()
