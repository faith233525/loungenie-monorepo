import re

with open('financials_best_revision.html', 'r', encoding='utf-8') as f:
    content = f.read()

print(f"Total content: {len(content)} chars\n")

# Find ALL links (PDF and otherwise relevant)
all_links = re.findall(r'href="([^"]+)"', content, re.I)
pdf_links = [l for l in all_links if '.pdf' in l.lower()]
other_links = [l for l in all_links if '.pdf' not in l.lower() and ('sedar' in l.lower() or 'financ' in l.lower() or 'loungenie' in l.lower() or 'poolsafe' in l.lower())]

print(f"PDF links found: {len(pdf_links)}")
print("-" * 80)
for i, link in enumerate(pdf_links, 1):
    # Try to find the surrounding anchor text
    idx = content.find(f'href="{link}"')
    if idx > -1:
        # Get surrounding text for context
        chunk = content[max(0, idx-200):idx+len(link)+200]
        # Extract anchor text
        text_match = re.search(r'>([^<]{3,120})</a>', chunk)
        anchor_text = text_match.group(1).strip() if text_match else "(no text)"
        print(f"\n{i}. {anchor_text}")
        print(f"   URL: {link}")

# Also look for any h1/h2/h3 headings for context
headings = re.findall(r'<h[1-4][^>]*>([^<]+)</h[1-4]>', content, re.I)
print(f"\n\nSection headings ({len(headings)}):")
for h in headings:
    print(f"  · {h.strip()}")
