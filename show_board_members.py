#!/usr/bin/env python3
import urllib.request, json, base64, re

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== BOARD MEMBERS CONTENT ===\n")

# Get board page
url = 'https://www.loungenie.com/wp-json/wp/v2/pages?slug=board&_fields=id,content,title'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    pages = json.loads(r.read())
    
    if pages:
        page = pages[0]
        title = page.get('title', {}).get('rendered', '')
        content = page.get('content', {}).get('rendered', '')
        page_id = page.get('id')
        
        print(f"Page: {title}")
        print(f"ID: {page_id}")
        print(f"URL: https://www.loungenie.com/board/")
        print(f"Content Length: {len(content)} chars\n")
        
        # Save full content for review
        with open('board_members_content.html', 'w', encoding='utf-8') as f:
            f.write(content)
        print("✓ Full content saved to: board_members_content.html\n")
        
        # Extract board member names and titles
        print("="*60)
        print("BOARD MEMBERS SECTION:")
        print("="*60)
        
        # Look for common patterns
        # Names in headings, titles, roles
        
        # Extract h2, h3 headers (usually board member names)
        headers = re.findall(r'<h[2-3][^>]*>([^<]+)</h[2-3]>', content)
        
        # Extract text in paragraphs and divs that might contain member info
        paragraphs = re.findall(r'<p[^>]*>([^<]*(?:<[^p][^>]*>[^<]*)*)</p>', content)
        
        # Look for specific patterns like "Name", "Title", "Role", "Director"
        member_patterns = re.findall(r'<strong>([^<]+)</strong>.*?<br[^>]*>\s*([^<]+)', content)
        
        if member_patterns:
            print("\nBoard Members (extracted):\n")
            for i, (name, title) in enumerate(member_patterns, 1):
                name = name.strip()
                title = title.strip()
                if name and title:
                    print(f"{i}. {name}")
                    print(f"   {title}\n")
        
        # Look for any text containing "CEO", "Director", "President", "Member"
        roles = re.findall(r'([^<>]*(?:CEO|Director|President|Chairman|Member|Officer)[^<>]*)', content)
        
        if roles and not member_patterns:
            print("\nRoles/Positions found:\n")
            for role in set(roles)[:10]:
                role = role.strip()
                if role and len(role) < 150:
                    print(f"  • {role}\n")
        
        # Extract actual HTML structure for names
        print("\n" + "="*60)
        print("BOARD SECTION HTML (first 2000 chars):")
        print("="*60)
        
        # Find board-related section
        board_match = re.search(r'<section[^>]*>.*?Board.*?</section>', content, re.DOTALL | re.IGNORECASE)
        if board_match:
            section = board_match.group(0)
            print(section[:1500])
            if len(section) > 1500:
                print(f"\n... [truncated, full content saved]")
        else:
            print(content[:1500])
            if len(content) > 1500:
                print(f"\n... [truncated, full content saved]")
        
    else:
        print("Board page not found")
        
except Exception as e:
    print(f"Error: {e}")

print("\n" + "="*60)
print("To see all board member details:")
print("="*60)
print("""
1. View saved HTML file: open board_members_content.html
2. Or go to: https://www.loungenie.com/board/
3. Check: Investors > Board menu link

The board members list is on this page with their:
  - Names
  - Titles/Positions
  - Biographies
  - Contact information (if available)
""")
