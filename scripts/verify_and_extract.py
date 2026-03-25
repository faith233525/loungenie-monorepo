import argparse
import hashlib
import os
import tarfile
import sys


def sha256_of_file(path, block_size=1 << 20):
    h = hashlib.sha256()
    total = 0
    with open(path, 'rb') as f:
        while True:
            data = f.read(block_size)
            if not data:
                break
            h.update(data)
            total += len(data)
    return h.hexdigest(), total


def is_within_directory(directory, target):
    abs_directory = os.path.abspath(directory)
    abs_target = os.path.abspath(target)
    return os.path.commonpath([abs_directory]) == os.path.commonpath([abs_directory, abs_target])


def safe_extract(tar, path="."):
    for member in tar.getmembers():
        member_path = os.path.join(path, member.name)
        if not is_within_directory(path, member_path):
            raise Exception("Attempted Path Traversal in Tar File")
    tar.extractall(path)


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--file', required=True)
    p.add_argument('--out', required=True)
    args = p.parse_args()

    if not os.path.exists(args.file):
        print('File not found:', args.file)
        sys.exit(2)

    print('Computing SHA256...')
    digest, size = sha256_of_file(args.file)
    print('SHA256:', digest)
    print('Size:', size)

    os.makedirs(args.out, exist_ok=True)

    print('Opening tar for extraction...')
    try:
        with tarfile.open(args.file, 'r:*') as tar:
            safe_extract(tar, args.out)
    except Exception as e:
        print('Extraction failed:', e)
        sys.exit(1)

    print('Extraction complete to', args.out)


if __name__ == '__main__':
    main()
