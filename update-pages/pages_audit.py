import json
import re
import csv
p = json.load(open('pages.json'))
rows = []
for page in p:
    cid = page.get('id')
    title = page.get('title',{}).get('rendered','').replace('\n',' ').strip()
    slug = page.get('slug','')
    status = page.get('status','')
    content = page.get('content',{}).get('rendered','') or ''
    has_style_tag = bool(re.search(r'<style\\b', content, re.I))
    has_script_tag = bool(re.search(r'<script\\b', content, re.I))
    inline_style_attrs = len(re.findall(r'style=["\']', content, re.I))
    inline_event_handlers = len(re.findall(r'on\\w+=["\']', content, re.I))
    raw_html_blocks = bool(re.search(r'<(div|section|span|table|figure|img|header|footer|nav|article|iframe)\\b', content, re.I))
    snippet = re.sub('\\s+',' ', re.sub('<[^>]+>','', content))[:200]
    rows.append((cid, title, slug, status, has_style_tag, has_script_tag, inline_style_attrs, inline_event_handlers, raw_html_blocks, len(content), snippet))
with open('pages_audit.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['id','title','slug','status','has_style_tag','has_script_tag','inline_style_attrs','inline_event_handlers','raw_html_blocks','content_length','text_snippet'])
    writer.writerows(rows)
print('WROTE pages_audit.csv with', len(rows), 'rows')
