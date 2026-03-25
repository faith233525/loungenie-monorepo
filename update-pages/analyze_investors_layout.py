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

# Get current Investors page (ID 5668)
url = "https://www.loungenie.com/wp-json/wp/v2/pages/5668"

try:
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=10) as response:
        page = json.loads(response.read().decode())
    
    content = page.get('content', {}).get('rendered', '')
    
    print(f"INVESTORS PAGE LAYOUT ANALYSIS")
    print(f"{'='*80}")
    print(f"Page ID: {page.get('id')}")
    print(f"Status: {page.get('status')}")
    print(f"Content size: {len(content)} chars")
    print()
    
    # Analyze structure
    import re
    
    # Count sections
    sections = len(re.findall(r'<section', content, re.I))
    divs = len(re.findall(r'<div[^>]*class="[^"]*ir-', content))
    cards = len(re.findall(r'<div[^>]*class="[^"]*(?:lg9-card|ir-content)', content))
    
    print(f"Structure Analysis:")
    print(f"  Sections: {sections}")
    print(f"  Investor-related divs: {divs}")
    print(f"  Cards: {cards}")
    print()
    
    # Check for layout classes
    if 'ir-shell' in content:
        print("✓ Using ir-shell layout system")
    if 'lg9' in content:
        print("✓ Using lg9 design system")
    if 'lg9-grid' in content:
        print("✓ Using lg9-grid layout")
    
    # Look for potential issues
    issues = []
    
    if content.count('<br />') > 20:
        issues.append("✗ Excessive <br /> tags (consider proper margin/padding)")
    
    if content.count('style="') > 30:
        issues.append("✗ Heavy inline styles (consider CSS classes)")
    
    if '<table' in content and '<table' not in content[content.find('<table'):content.find('<table')+1000]:
        if 'border-collapse' not in content:
            issues.append("⚠ Tables may need responsive styling")
    
    # Check for responsive issues
    if '@media' not in content or content.count('@media') < 3:
        issues.append("⚠ Limited responsive breakpoints")
    
    # Save full content
    with open('investors_page_current.html', 'w', encoding='utf-8') as f:
        f.write(content)
    
    print()
    if issues:
        print("Potential Layout Issues Found:")
        for issue in issues:
            print(f"  {issue}")
    else:
        print("Layout structure appears sound. Specific issues?")
    
    print()
    print("✓ Full page content saved to investors_page_current.html")
    
except Exception as e:
    print(f"Error: {e}")
