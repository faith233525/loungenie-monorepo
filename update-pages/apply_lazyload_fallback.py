import urllib.request
import json
import base64

user = 'admin'
password = 'i6IM cqLZ vQDC pIRk nKFr g35i'
headers = {
    'Authorization': 'Basic ' + base64.b64encode((user + ':' + password).encode()).decode(),
    'Content-Type': 'application/json',
}
base = 'https://www.loungenie.com/wp-json/wp/v2'

# Core custom pages using lg9 markup
page_ids = [4701, 2989, 4862, 5139, 5223, 5285, 5651, 5668, 5686, 5716]

fallback_block = '''\n<!-- wp:html -->
<script>
(function () {
  function forceLoadLazyImages(scope) {
    var root = scope || document;
    var imgs = root.querySelectorAll('img[data-src], img[data-lazy-src], img[data-srcset]');
    imgs.forEach(function (img) {
      var src = img.getAttribute('data-src') || img.getAttribute('data-lazy-src');
      var srcset = img.getAttribute('data-srcset');
      var current = img.getAttribute('src') || '';
      if (src && (!current || current.indexOf('data:image') === 0)) {
        img.setAttribute('src', src);
      }
      if (srcset && !img.getAttribute('srcset')) {
        img.setAttribute('srcset', srcset);
      }
      img.classList.add('litespeed-no-lazyload');
      img.setAttribute('loading', 'eager');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { forceLoadLazyImages(document); });
  } else {
    forceLoadLazyImages(document);
  }

  // Retry once after initial scripts/layout shifts
  setTimeout(function () { forceLoadLazyImages(document); }, 1200);
})();
</script>
<!-- /wp:html -->\n'''

marker = 'forceLoadLazyImages'

for pid in page_ids:
    # Fetch page raw content
    req = urllib.request.Request(f'{base}/pages/{pid}?context=edit', headers=headers)
    with urllib.request.urlopen(req, timeout=25) as r:
        page = json.loads(r.read())

    raw = page['content']['raw']
    if marker in raw:
        print(f'page {pid}: already has fallback, skipped')
        continue

    updated = raw + fallback_block

    payload = json.dumps({'content': updated}).encode()
    req2 = urllib.request.Request(f'{base}/pages/{pid}', data=payload, headers=headers, method='POST')
    with urllib.request.urlopen(req2, timeout=25) as r2:
        res = json.loads(r2.read())
    print(f'page {pid}: updated ({res.get("status")})')

print('done')
