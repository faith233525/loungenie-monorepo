import csv
import sys
import time
from urllib.parse import urljoin

try:
    import requests
    from bs4 import BeautifulSoup
except Exception:
    print('Missing requirements. Run: pip install requests beautifulsoup4 lxml')
    sys.exit(1)

BASE = 'https://loungenie.com/loungenie/'
PAGES = [
    BASE,
    urljoin(BASE, 'index.php/poolside-amenity-unit/'),
    urljoin(BASE, 'index.php/loungenie-videos/'),
    urljoin(BASE, 'cabana-installation-photos/'),
    urljoin(BASE, 'index.php/hospitality-innovation/'),
    urljoin(BASE, 'index.php/contact-loungenie/'),
    urljoin(BASE, 'index.php/investors/'),
    urljoin(BASE, 'index.php/financials/'),
    urljoin(BASE, 'index.php/press/')
]
OUT = r'C:\temp\content_seo_audit.csv'

session = requests.Session()
session.headers.update({'User-Agent': 'ContentSEOAuditor/1.0'})


def analyze(url):
    try:
        r = session.get(url, timeout=15)
        r.raise_for_status()
    except Exception as e:
        return {'url': url, 'error': str(e)}
    soup = BeautifulSoup(r.text, 'lxml')
    title = soup.title.string.strip() if soup.title and soup.title.string else ''
    meta_desc = ''
    m = soup.find('meta', attrs={'name': lambda x: x and x.lower()=='description'})
    if m and m.get('content'):
        meta_desc = m['content'].strip()
    h1 = ''
    h = soup.find('h1')
    if h:
        h1 = ' '.join(h.stripped_strings)
    imgs = soup.find_all('img')
    total_imgs = len(imgs)
    missing_alt = sum(1 for img in imgs if not (img.get('alt') and img.get('alt').strip()))
    first_paragraph = ''
    p = soup.find('p')
    if p:
        first_paragraph = ' '.join(p.stripped_strings)[:200]
    return {
        'url': url,
        'status_code': r.status_code,
        'title': title,
        'meta_description': meta_desc,
        'h1': h1,
        'total_images': total_imgs,
        'missing_alt': missing_alt,
        'first_paragraph': first_paragraph
    }


def main():
    rows = []
    for url in PAGES:
        print('Analyzing', url)
        data = analyze(url)
        rows.append(data)
        time.sleep(0.25)

    keys = ['url','status_code','title','meta_description','h1','total_images','missing_alt','first_paragraph','error']
    with open(OUT, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=keys)
        writer.writeheader()
        for r in rows:
            row = {k: r.get(k, '') for k in keys}
            writer.writerow(row)
    print('Wrote', OUT)


if __name__ == '__main__':
    main()
