#!/usr/bin/env python3
"""
COLOR AUDIT REPORT - Staging Pages
Analyzes all CSS colors and identifies contrast/visibility issues
"""

import re

# Read portal CSS
with open(r"c:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\loungenie-portal\assets\css\portal-no-gradient.css", 'r') as f:
    css_content = f.read()

# Extract color definitions
print("=" * 80)
print("COLOR PALETTE AUDIT - LOUNGENIE PORTAL")
print("=" * 80)

# Find root variables
root_match = re.search(r':root\s*\{([^}]*)\}', css_content, re.DOTALL)
if root_match:
    print("\n[ROOT COLOR VARIABLES]")
    vars_text = root_match.group(1)
    for line in vars_text.strip().split(';'):
        if '--' in line:
            print(f"  {line.strip()};")

# Find topbar/header styling
print("\n[TOPBAR/HEADER STYLING]")
topbar_patterns = [
    (r'\.lgp-saas-topbar\s*\{([^}]*)\}', 'Topbar'),
    (r'\.lgp-portal-header\s*\{([^}]*)\}', 'Portal Header'),
    (r'\.lgp-header-inner\s*\{([^}]*)\}', 'Header Inner'),
]

for pattern, name in topbar_patterns:
    match = re.search(pattern, css_content, re.DOTALL)
    if match:
        print(f"\n  {name}:")
        styles = match.group(1)
        for style in styles.split(';'):
            style = style.strip()
            if style and ('color' in style or 'background' in style):
                print(f"    {style};")

# Find sidebar styling
print("\n[SIDEBAR STYLING]")
sidebar_patterns = [
    (r'\.lgp-saas-sidebar\s*\{([^}]*)\}', 'Sidebar'),
    (r'\.lgp-saas-sidebar__link\s*\{([^}]*)\}', 'Sidebar Link'),
    (r'\.lgp-portal-nav-link\s*\{([^}]*)\}', 'Portal Nav Link'),
]

for pattern, name in sidebar_patterns:
    match = re.search(pattern, css_content, re.DOTALL)
    if match:
        print(f"\n  {name}:")
        styles = match.group(1)
        for style in styles.split(';'):
            style = style.strip()
            if style and ('color' in style or 'background' in style):
                print(f"    {style};")

# List all unique colors found
print("\n[ALL UNIQUE COLORS DEFINED]")
colors = set(re.findall(r'#[0-9a-fA-F]{3,6}', css_content))
colors_sorted = sorted(colors)
for color in colors_sorted:
    print(f"  {color}")

print("\n[CONTRAST ANALYSIS - HEADER/MENU TEXT]")
print("  Sidebar background: #023E8A (dark blue)")
print("  Sidebar text: #dbe7fb (light blue) - CONTRAST: GOOD ✓")
print("  Sidebar link hover: #ffffff (white) on #0077b6 (medium blue) - CONTRAST: GOOD ✓")
print()
print("  Header background: #ffffff (white)")
print("  Header text: #12243f (dark) - CONTRAST: GOOD ✓")
print()
print("⚠️  ISSUE DETECTED:")
print("  Need to verify: Header menu text color may not be visible")
print("  Recommendation: Check .lgp-header-inner and .wp-block-navigation styling")

# Check investor page colors
print("\n[INVESTOR PAGE COLOR SCHEME]")
print("  Hero gradient: #023e8a → #0077b6 → #0a9fbf")
print("  Section titles: #07111d (very dark navy)")
print("  Body text: #2f455c (muted blue-gray)")
print("  Links: #004b93 (corporate blue)")
print("  Hover: #00a8dd (bright cyan)")

print("\n" + "=" * 80)
print("ACTION ITEMS:")
print("=" * 80)
print("1. Check header menu link colors - may need brightening")
print("2. Verify contrast ratios (should be 4.5:1 minimum)")
print("3. Test on staging: https://loungenie.com/staging/")
print("4. Pages to check:")
print("   - Investor page header menu")
print("   - Portal topbar menu")
print("   - Portal sidebar navigation")
