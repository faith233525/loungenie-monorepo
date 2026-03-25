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

pages = {
    "Financials": 5686,
    "Press": 5716
}

for page_name, page_id in pages.items():
    print(f"\n{'='*80}")
    print(f"{page_name.upper()} PAGE (ID {page_id})")
    print(f"{'='*80}")
    
    url = f"https://www.loungenie.com/wp-json/wp/v2/pages/{page_id}/revisions?per_page=100"
    
    try:
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=10) as response:
            revisions = json.loads(response.read().decode())
        
        print(f"Total revisions: {len(revisions)}\n")
        print(f"{'Revision ID':<12} {'Date':<25} {'Characters':<12} {'Content Preview':<40}")
        print("-" * 90)
        
        # Find max and min content sizes
        sizes = [len(r.get('content', {}).get('rendered', '')) for r in revisions]
        max_size = max(sizes) if sizes else 0
        min_size = min(sizes) if sizes else 0
        
        # Show first 10 and last 10 revisions
        for rev in revisions[:10]:
            rev_id = rev.get('id')
            date = rev.get('modified', 'N/A')[:19]
            content = rev.get('content', {}).get('rendered', '')
            content_len = len(content)
            preview = content[:35].replace('\n', ' ')[:35] + "..." if content else "(empty)"
            
            print(f"{rev_id:<12} {date:<25} {content_len:<12} {preview:<40}")
        
        if len(revisions) > 10:
            print(f"... ({len(revisions) - 20} more revisions) ...")
            for rev in revisions[-10:]:
                rev_id = rev.get('id')
                date = rev.get('modified', 'N/A')[:19]
                content = rev.get('content', {}).get('rendered', '')
                content_len = len(content)
                preview = content[:35].replace('\n', ' ')[:35] + "..." if content else "(empty)"
                
                print(f"{rev_id:<12} {date:<25} {content_len:<12} {preview:<40}")
        
        print(f"\nCurrent size: {sizes[0]} chars")
        print(f"Oldest size: {sizes[-1]} chars")
        print(f"Max size in history: {max_size} chars")
        print(f"Min size in history: {min_size} chars")
        
        # Check if there's a substantial difference
        current = sizes[0]
        oldest = sizes[-1]
        max_avail = max_size
        
        if max_avail > current * 1.5:
            print(f"\n⚠️  FOUND RICHER VERSION: {max_avail} chars available (current: {current})")
            # Find which revision has the max
            max_rev = [r for r in revisions if len(r.get('content', {}).get('rendered', '')) == max_avail][0]
            print(f"   → In revision {max_rev.get('id')} ({max_rev.get('modified')})")
        
    except Exception as e:
        print(f"Error: {e}")
