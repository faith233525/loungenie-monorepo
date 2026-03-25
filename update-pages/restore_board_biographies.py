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

# Get revision 7609 which has the full biographical content (6,844 chars)
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5651/revisions/7609"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        revision = json.loads(response.read().decode())
    
    full_content = revision.get('content', {}).get('rendered', '')
    
    print(f"Retrieved revision 7609 with {len(full_content)} chars")
    print(f"\nExtracted member biographies:")
    print("=" * 80)
    
    # Parse and extract each member biography
    if "David Berger" in full_content:
        start = full_content.find("David Berger")
        end = full_content.find("Steven Glaser", start)
        david_section = full_content[start:end] if end > start else full_content[start:start+800]
        
        # Extract just the biography paragraph
        if "Mr. David Berger" in david_section:
            bio_start = david_section.find("Mr. David Berger")
            bio_end = david_section.find("</p>", bio_start) + 4
            david_bio = david_section[bio_start:bio_end]
            print("DAVID BERGER:")
            print(david_bio)
            print()
    
    # Save full revision to file for restoration
    with open('board_revision_7609_full.html', 'w', encoding='utf-8') as f:
        f.write(full_content)
    
    print(f"\n✓ Full revision 7609 content saved to board_revision_7609_full.html ({len(full_content)} chars)")
    
    # Also look for other members
    members = ["Steven Glaser", "Steven Mintz", "Gillian Deacon", "Robert Pratt"]
    for member in members:
        if member in full_content:
            count = full_content.find(f"<p>Mr. {member}") if member.startswith("Steven") or member.startswith("Robert") else full_content.find(f"Ms. {member}")
            if count == -1:
                count = full_content.find(f"<p>Ms. {member}")
            if count > -1:
                print(f"✓ {member} has biographical entry")
    
except Exception as e:
    print(f"Error: {e}")
