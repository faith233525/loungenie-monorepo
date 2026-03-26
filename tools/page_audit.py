#!/usr/bin/env python3
"""Simple page audit tool.
Usage: python page_audit.py --iterations 100 --urls url1 url2 ...
"""
import argparse
import requests
import time
from statistics import mean


def audit_page(url, iterations=10, timeout=15):
    results = []
    session = requests.Session()
    headers = {"User-Agent": "LG9-Audit/1.0"}
    for i in range(iterations):
        start = time.time()
        try:
            r = session.get(url, headers=headers, timeout=timeout)
            elapsed = time.time() - start
            status = r.status_code
            html = r.text
            has_inline = 'lg9-shell-inline' in html
            has_plugin_css = 'lg-block-patterns' in html or 'lg9-site' in html
            img_count = html.count('<img ')
            results.append({
                'ok': True,
                'status': status,
                'time': elapsed,
                'has_inline': has_inline,
                'has_plugin_css': has_plugin_css,
                'img_count': img_count,
            })
        except Exception as e:
            elapsed = time.time() - start
            results.append({'ok': False, 'error': str(e), 'time': elapsed})
    return results


def summarize(url, results):
    total = len(results)
    oks = [r for r in results if r.get('ok')]
    fails = [r for r in results if not r.get('ok')]
    status_counts = {}
    times = [r['time'] for r in oks]
    has_inline_count = sum(1 for r in oks if r.get('has_inline'))
    has_plugin_css_count = sum(1 for r in oks if r.get('has_plugin_css'))
    img_counts = [r['img_count'] for r in oks]
    for r in oks:
        status_counts[r['status']] = status_counts.get(r['status'], 0) + 1

    return {
        'url': url,
        'total': total,
        'success': len(oks),
        'fail': len(fails),
        'status_counts': status_counts,
        'avg_time': mean(times) if times else None,
        'min_time': min(times) if times else None,
        'max_time': max(times) if times else None,
        'has_inline_pct': (has_inline_count / len(oks) * 100) if oks else 0,
        'has_plugin_css_pct': (has_plugin_css_count / len(oks) * 100) if oks else 0,
        'avg_img_count': mean(img_counts) if img_counts else 0,
        'fail_examples': [f.get('error') for f in fails[:3]],
    }


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--iterations', type=int, default=10)
    p.add_argument('--timeout', type=int, default=15)
    p.add_argument('--urls', nargs='+', required=True)
    args = p.parse_args()

    reports = []
    for u in args.urls:
        print(f"Auditing {u} for {args.iterations} iterations...")
        res = audit_page(u, iterations=args.iterations, timeout=args.timeout)
        summary = summarize(u, res)
        reports.append(summary)
        print(f"  {summary['success']}/{summary['total']} OK, avg {summary['avg_time']:.2f}s, inline {summary['has_inline_pct']:.1f}%, plugin css {summary['has_plugin_css_pct']:.1f}%")

    print('\nFull report:')
    for r in reports:
        print('\nURL:', r['url'])
        for k in ('total','success','fail','avg_time','min_time','max_time','has_inline_pct','has_plugin_css_pct','avg_img_count'):
            print(f"  {k}: {r.get(k)}")
        print('  status_counts:', r['status_counts'])
        if r['fail_examples']:
            print('  fail_examples:', r['fail_examples'])


if __name__ == '__main__':
    main()
