#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

page_slug = 'investors'

print(f"=== Full Content Audit: {page_slug} ===\n")

url = f'https://www.loungenie.com/wp-json/wp/v2/pages?slug={page_slug}&_fields=id,title,content,featured_media'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    pages = json.loads(r.read())
    
    if pages:
        page = pages[0]
        content = page.get('content', {}).get('rendered', '')
        
        # Write full content to file for inspection
        with open('investor_page_full_content.html', 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"✓ Exported full page content to: investor_page_full_content.html")
        print(f"\nContent length: {len(content)} characters")
        print(f"\nContent structure analysis:")
        
        # Count key HTML elements
        import re
        
        sections = {
            'sections (divs with class)': len(re.findall(r'<div[^>]*class=["\'][^"\']*["\'][^>]*>', content)),
            'images': len(re.findall(r'<img[^>]*>', content)),
            'videos/iframes': len(re.findall(r'<iframe[^>]*>', content)),
            'headers': len(re.findall(r'<h[1-6][^>]*>', content)),
            'paragraphs': len(re.findall(r'<p[^>]*>', content)),
            'links': len(re.findall(r'<a[^>]*href=', content)),
            'buttons': len(re.findall(r'<button|<a[^>]*class=["\'][^"\']*button', content)),
        }
        
        for tag, count in sections.items():
            print(f"  {tag}: {count}")
        
        # Look for incomplete tags or broken HTML
        if '[' in content and ']' in content:
            import re
            broken = re.findall(r'\[([^\]]{20,})\]', content)
            if broken:
                print(f"\n⚠️  Found bracket tags (possible shortcodes or placeholders):")
                for b in broken[:5]:
                    print(f"  [{b}]")
        
        # Check for comment tags with warnings
        comments = re.findall(r'<!--\s*([^-]*?)\s*-->', content)
        if comments:
            print(f"\nHTML comments found: {len(comments)}")
            for c in comments[:3]:
                if 'missing' in c.lower() or 'todo' in c.lower() or 'remove' in c.lower():
                    print(f"  ⚠️  {c[:80]}")

except Exception as e:
    print(f"Error: {e}")

print("\n" + "="*60)
print("To view the downloaded content in detail:")
print("  open investor_page_full_content.html")
