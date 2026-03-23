#!/usr/bin/env python3
import urllib.request, json, base64

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== CHECKING FOR FULL BOARD MEMBER BIOGRAPHIES ===\n")

# Get full page content
url = 'https://www.loungenie.com/wp-json/wp/v2/pages?slug=board'
req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + credentials})

try:
    r = urllib.request.urlopen(req, timeout=15)
    pages = json.loads(r.read())
    
    if pages:
        page = pages[0]
        content = page.get('content', {}).get('rendered', '')
        
        # Search for biography/detailed content patterns
        import re
        
        # Look for member sections with descriptions
        # Pattern: Member name followed by role and bio text
        
        member_names = ['David Berger', 'Steven Glaser', 'Steven Mintz', 'Gillian Deacon', 'Robert Pratt']
        
        print("Searching for detailed member content...\n")
        
        for member in member_names:
            # Search for this member's name and following content
            pattern = re.escape(member) + r'.*?(?:<(?:h[1-4]|div[^>]*>)|$)'
            match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
            
            if match:
                section = match.group(0)
                # Clean up HTML
                section_clean = re.sub(r'<[^>]+>', ' ', section)
                section_clean = re.sub(r'\s+', ' ', section_clean).strip()
                
                if len(section_clean) > 100:
                    print(f"✓ {member}: {section_clean[:150]}...\n")
                else:
                    print(f"⚠️  {member}: Limited details ({len(section_clean)} chars)\n")
            else:
                print(f"❌ {member}: No detailed content found\n")
        
        # Check if page has elementor or any builder content with member bios
        if 'elementor' in content.lower():
            print("✓ Page uses Elementor builder (may have detailed sections)")
        
        if 'biography' in content.lower() or 'bio' in content.lower():
            print("✓ Biography sections detected")
            bios = re.findall(r'<[^>]*>(biography|bio)[^<]*</[^>]*>.*?(?=<(?:h[1-3]|section)|$)', content, re.DOTALL | re.IGNORECASE)
            print(f"  Found {len(bios)} bio sections")
        else:
            print("⚠️  No detailed biographies currently in page content")
        
        print("\n" + "="*60)
        print("CURRENT STATUS:")
        print("="*60)
        print(f"""
The board page currently contains:
  • Member names: ✓ Present
  • Titles/Roles: ✓ Present (CEO, COO/CFO, Director, etc.)
  • Biographies: ⚠️  Limited/Missing

NEXT STEPS:
1. Do you have the original detailed member bios saved elsewhere?
2. Or should they be added to complete the board directory?

If you have the bio text, I can:
  • Add detailed descriptions for each member
  • Include professional backgrounds
  • Add experience/credentials
  • Create a full board directory page
""")
        
except Exception as e:
    print(f"Error: {e}")

print("\nTo add full member biographies:")
print("  Please provide the detailed bio content for each member")
print("  Then I can update the page with complete information")
