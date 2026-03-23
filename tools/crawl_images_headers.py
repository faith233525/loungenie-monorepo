import os
import re
import csv
import time
import sys
from urllib.parse import urljoin, urlparse

try:
    import requests
except Exception:
    print('The requests library is required. Run: pip install requests')
    sys.exit(1)

BASE = 'https://loungenie.com/loungenie/'
SITEMAP_INDEX = urljoin(BASE, 'sitemap_index.xml')
OUT_CSV = r'C:\temp\images_headers.csv'

session = requests.Session()
session.headers.update({'User-Agent': 'ImageHeaderCrawler/1.0'})


def fetch_text(url, timeout=20):
    r = session.get(url, timeout=timeout)
    r.raise_for_status()
    return r.text


def parse_sitemap_for_pages(sitemap_index_text):
    # find sitemap URLs, then fetch page-sitemap and collect <loc>
    sitemaps = re.findall(r'<loc>(.*?)</loc>', sitemap_index_text, re.I)
    pages = []
    for sm in sitemaps:
        if 'page-sitemap' in sm or 'page' in sm:
            try:
                txt = fetch_text(sm)
                locs = re.findall(r'<loc>(.*?)</loc>', txt, re.I)
                pages.extend(locs)
            except Exception:
                continue
    return list(dict.fromkeys(pages))


class ImgExtractor:
    IMG_RE = re.compile(r'<img[^>]+src\s*=\s*"([^"]+)"', re.I)
    SRCSET_RE = re.compile(r'srcset\s*=\s*"([^"]+)"', re.I)
    CSS_URL_RE = re.compile(r'url\(([^)]+)\)')

    @staticmethod
    def extract(html, base):
        imgs = set()
        for m in ImgExtractor.IMG_RE.findall(html):
            imgs.add(urljoin(base, m))
        for m in ImgExtractor.SRCSET_RE.findall(html):
            parts = [p.strip().split(' ')[0] for p in m.split(',') if p.strip()]
            for p in parts:
                imgs.add(urljoin(base, p))
        for m in ImgExtractor.CSS_URL_RE.findall(html):
            cleaned = m.strip(' \"\'')
            if cleaned:
                imgs.add(urljoin(base, cleaned))
        return imgs


def gather_image_urls(page_urls):
    image_urls = {}
    for page in page_urls:
        try:
            txt = fetch_text(page)
        except Exception:
            continue
        imgs = ImgExtractor.extract(txt, page)
        for img in imgs:
            image_urls.setdefault(img, []).append(page)
        time.sleep(0.3)
    return image_urls


def head_info(url):
    try:
        r = session.head(url, allow_redirects=True, timeout=20)
        if r.status_code == 405:
            r = session.get(url, stream=True, timeout=20)
            r.close()
    except Exception as e:
        return {'status': 'error', 'error': str(e)}
    headers = r.headers
    return {
        'status': r.status_code,
        'content_type': headers.get('Content-Type',''),
        'content_length': headers.get('Content-Length',''),
        'cache_control': headers.get('Cache-Control',''),
        'expires': headers.get('Expires',''),
        'x_litespeed_cache': headers.get('X-LiteSpeed-Cache',''),
        'x_lscache': headers.get('X-Lscache',''),
        'server': headers.get('Server','')
    }


def ensure_out_dir(path):
    d = os.path.dirname(path)
    if d and not os.path.exists(d):
        os.makedirs(d)


def main():
    print('Fetching sitemap index:', SITEMAP_INDEX)
    try:
        si = fetch_text(SITEMAP_INDEX)
    except Exception:
        print('Failed to fetch sitemap_index.xml; falling back to page-sitemap.xml')
        try:
            si = fetch_text(urljoin(BASE, 'page-sitemap.xml'))
        except Exception as e:
            print('Cannot fetch sitemaps:', e)
            sys.exit(1)

    pages = parse_sitemap_for_pages(si)
    if not pages:
        # fallback: use known main pages
        pages = [BASE, urljoin(BASE,'index.php/poolside-amenity-unit/'), urljoin(BASE,'index.php/contact-loungenie/'), urljoin(BASE,'cabana-installation-photos/')]

    print(f'Found {len(pages)} pages to scan')
    image_map = gather_image_urls(pages)
    print(f'Found {len(image_map)} unique image URLs')

    ensure_out_dir(OUT_CSV)
    with open(OUT_CSV, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['image_url','page_urls','status','content_type','content_length','cache_control','expires','x_litespeed_cache','x_lscache','server','is_webp','error'])
        for i, (img, pages) in enumerate(image_map.items(), 1):
            info = head_info(img)
            is_webp = '.webp' in img.lower() or (isinstance(info, dict) and 'content_type' in info and 'webp' in (info.get('content_type') or '').lower())
            if info.get('status') == 'error' or info.get('status') is None:
                writer.writerow([img,';'.join(pages),info.get('status',''),info.get('content_type',''),info.get('content_length',''),info.get('cache_control',''),info.get('expires',''),info.get('x_litespeed_cache',''),info.get('x_lscache',''),info.get('server',''),is_webp, info.get('error','')])
            else:
                writer.writerow([img,';'.join(pages),info.get('status',''),info.get('content_type',''),info.get('content_length',''),info.get('cache_control',''),info.get('expires',''),info.get('x_litespeed_cache',''),info.get('x_lscache',''),info.get('server',''),is_webp,''])
            if i % 20 == 0:
                print(f'Processed {i} images')
            time.sleep(0.15)

    print('Wrote', OUT_CSV)


if __name__ == '__main__':
    main()
