import argparse
import tarfile
import sys


def list_contents(path, max_list=200):
    with tarfile.open(path, 'r:*') as tar:
        members = tar.getmembers()
        top_levels = {}
        for m in members:
            parts = m.name.split('/')
            top = parts[0] if parts else m.name
            top_levels[top] = top_levels.get(top, 0) + 1

        print('Top-level entries and counts:')
        for k, v in sorted(top_levels.items(), key=lambda x: (-x[1], x[0])):
            print(f'{k}: {v}')

        print('\nFirst {} entries:'.format(min(max_list, len(members))))
        for i, m in enumerate(members[:max_list], 1):
            print(f'{i:5d}: {m.name}')


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--file', required=True)
    p.add_argument('--max', type=int, default=200)
    args = p.parse_args()

    try:
        list_contents(args.file, args.max)
    except Exception as e:
        print('Error listing tar contents:', e, file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
