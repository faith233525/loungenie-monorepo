import urllib.request
import urllib.error
import json
import base64

# Auth credentials
user = "admin"
app_password = "i6IM cqLZ vQDC pIRk nKFr g35i"
auth_str = base64.b64encode(f"{user}:{app_password}".encode()).decode()

headers = {
    "Accept": "application/json",
    "Authorization": f"Basic {auth_str}"
}

# Financials page ID: 5686
# Revision 8627 was the last known good version with 27,250 chars (all PDFs intact)
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5686/revisions?per_page=100"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        revisions = json.loads(response.read().decode())

    print(f"Total revisions: {len(revisions)}")
    print(f"\n{'Revision ID':<12} {'Date':<25} {'Characters':<12}")
    print("-" * 50)
    for rev in revisions[:15]:
        print(f"{rev.get('id'):<12} {str(rev.get('modified',''))[:19]:<25} {len(rev.get('content',{}).get('rendered','')):<12}")

    # Find the revision with the most real content (should have ~27000 chars with actual PDF links)
    best = max(revisions, key=lambda r: len(r.get('content',{}).get('rendered','')))
    best_content = best.get('content',{}).get('rendered','')
    
    print(f"\nBest revision: ID {best.get('id')} — {len(best_content)} chars")
    print(f"Date: {best.get('modified')}")
    
    # Check it has actual financial links
    has_pdfs = '.pdf' in best_content.lower()
    has_financial = 'financ' in best_content.lower()
    print(f"Has PDF links: {has_pdfs}")
    print(f"Has financial keyword: {has_financial}")
    
    # Save it
    with open('financials_best_revision.html', 'w', encoding='utf-8') as f:
        f.write(best_content)
    print(f"\n✓ Saved to financials_best_revision.html")

    # Show a preview to confirm it has real content
    import re
    links = re.findall(r'href="([^"]+\.pdf[^"]*)"', best_content, re.I)
    print(f"\nPDF links found ({len(links)}):")
    for link in links[:10]:
        print(f"  → {link[:80]}")

except Exception as e:
    print(f"Error: {e}")
