#!/usr/bin/env python3
"""
DETAILED INVESTOR PAGE CONTENT ANALYSIS
Verifies specific content requirements and generates detailed quality report
"""

import requests
import json
import re
from html import unescape

# Configuration
STAGING_URL = "https://loungenie.com/staging"
WP_API_URL = f"{STAGING_URL}/wp-json/wp/v2"
AUTH = ("Copilot", "U7GM Z9qE QOq6 MQva IzcQ 6PU2")

# Page IDs and their specific requirements
PAGE_REQUIREMENTS = {
    5668: {
        "name": "Main Investor Page",
        "required_blocks": ["corporate-details", "ir-contact"],
        "required_sections": [
            "Corporate Address",
            "Stock Information", 
            "Auditors",
            "Lawyers",
            "Transfer Agent",
            "Investor Relations Contact"
        ]
    },
    5651: {
        "name": "Board of Directors",
        "required_blocks": [],
        "required_sections": ["Board of Directors"]
    },
    5686: {
        "name": "Financials",
        "required_blocks": ["filings-index"],
        "required_sections": ["Required Filing Index", "Financial Reports"]
    },
    5716: {
        "name": "Press & News",
        "required_blocks": ["press-archive"],
        "required_sections": ["Press Releases", "Archive"]
    }
}

def get_page_content(page_id):
    """Fetch full page content from WordPress"""
    try:
        url = f"{WP_API_URL}/pages/{page_id}?context=edit"
        response = requests.get(url, auth=AUTH, verify=False)
        response.raise_for_status()
        return response.json()
    except Exception as e:
        print(f"Error fetching page {page_id}: {e}")
        return None

def extract_text_content(html):
    """Extract plain text from HTML"""
    # Remove HTML tags
    text = re.sub(r'<[^>]+>', '', html)
    # Remove extra whitespace
    text = re.sub(r'\s+', ' ', text)
    # Decode HTML entities
    text = unescape(text)
    return text.strip()

def check_required_sections(html_content, required_sections):
    """Check if required sections are present in content"""
    results = {}
    content_lower = extract_text_content(html_content).lower()
    
    for section in required_sections:
        found = section.lower() in content_lower
        results[section] = found
    
    return results

def extract_contacts_and_details(html_content):
    """Extract contact info, links, and key details from content"""
    contacts = {
        "email_addresses": [],
        "phone_numbers": [],
        "external_links": [],
        "documents": [],
        "company_names": []
    }
    
    # Extract emails
    emails = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', html_content)
    contacts["email_addresses"] = list(set(emails))
    
    # Extract phone numbers
    phones = re.findall(r'(?:\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}', html_content)
    contacts["phone_numbers"] = list(set(phones))
    
    # Extract links
    links = re.findall(r'href=["\']([^"\']+)["\']', html_content)
    external = [l for l in links if l.startswith('http')]
    contacts["external_links"] = list(set(external[:10]))  # Top 10
    
    # Extract PDF/document references
    docs = re.findall(r'(?:href=["\'])?([^"\'<>]*\.(?:pdf|docx?|xls[x]?|xlsx))["\']?', html_content, re.IGNORECASE)
    contacts["documents"] = list(set(docs[:10]))
    
    # Look for company names (heuristic)
    company_keywords = ['Inc', 'Corp', 'Limited', 'Ltd', 'Inc.', 'Corp.', 'Company', 'Holdings']
    text = extract_text_content(html_content)
    for keyword in company_keywords:
        matches = re.findall(r'[A-Z][a-zA-Z\s&]+' + keyword, text)
        if matches:
            contacts["company_names"].extend(matches)
    contacts["company_names"] = list(set(contacts["company_names"][:5]))
    
    return contacts

def check_featured_image(page_data):
    """Get featured image details"""
    image_id = page_data.get('featured_media', 0)
    
    if image_id == 0:
        return {
            "present": False,
            "id": None,
            "url": None,
            "alt": None
        }
    
    try:
        img_url = f"{WP_API_URL}/media/{image_id}"
        img_response = requests.get(img_url, auth=AUTH, verify=False)
        img_response.raise_for_status()
        img_data = img_response.json()
        
        return {
            "present": True,
            "id": image_id,
            "url": img_data.get('source_url', ''),
            "alt": img_data.get('alt_text', ''),
            "title": img_data.get('title', {}).get('rendered', ''),
            "description": img_data.get('description', {}).get('rendered', '')
        }
    except Exception as e:
        return {
            "present": True,
            "id": image_id,
            "url": None,
            "alt": None,
            "error": str(e)
        }

def analyze_page_in_depth(page_id):
    """Perform detailed analysis of a page"""
    
    print(f"\n{'='*80}")
    print(f"DETAILED ANALYSIS: Page {page_id}")
    print(f"{'='*80}\n")
    
    page_data = get_page_content(page_id)
    if not page_data:
        print("Failed to fetch page data")
        return None
    
    requirements = PAGE_REQUIREMENTS.get(page_id, {})
    title = page_data.get('title', {}).get('rendered', 'Unknown')
    html_content = page_data.get('content', {}).get('rendered', '')
    
    analysis = {
        "page_id": page_id,
        "title": title,
        "url": page_data.get('link', ''),
        "status": page_data.get('status', ''),
        "requirements": requirements
    }
    
    # 1. Featured Image Analysis
    print("[1] FEATURED IMAGE")
    print("─" * 40)
    img_info = check_featured_image(page_data)
    analysis["featured_image"] = img_info
    
    if img_info["present"]:
        print(f"  ✓ Featured image present (ID: {img_info['id']})")
        if img_info.get('url'):
            print(f"    URL: {img_info['url'][:70]}...")
        if img_info.get('alt'):
            print(f"    Alt Text: {img_info['alt']}")
        else:
            print(f"    ⚠ Missing alt text for featured image")
    else:
        print(f"  ✗ No featured image (hero image missing)")
    
    # 2. Content Metrics
    print("\n[2] CONTENT METRICS")
    print("─" * 40)
    
    text_content = extract_text_content(html_content)
    word_count = len(text_content.split())
    char_count = len(text_content)
    
    print(f"  Text Length: {char_count:,} characters")
    print(f"  Approximate Words: {word_count:,}")
    print(f"  Content Assessment: {'✓ Substantial' if char_count > 3000 else '⚠ May need more content'}")
    
    analysis["content_metrics"] = {
        "char_count": char_count,
        "word_count": word_count,
        "lines": len(text_content.split('\n'))
    }
    
    # 3. Required Sections Verification
    print("\n[3] REQUIRED SECTIONS")
    print("─" * 40)
    
    if "required_sections" in requirements:
        sections_check = check_required_sections(html_content, requirements["required_sections"])
        analysis["sections_check"] = sections_check
        
        all_found = True
        for section, found in sections_check.items():
            status = "✓" if found else "✗"
            print(f"  {status} {section}: {'Found' if found else 'NOT FOUND'}")
            if not found:
                all_found = False
        
        if all_found:
            print(f"\n  ✓ All required sections present")
        else:
            print(f"\n  ⚠ Some required sections missing")
    
    # 4. Contact Information & Links
    print("\n[4] CONTACT & REFERENCE INFORMATION")
    print("─" * 40)
    
    contacts = extract_contacts_and_details(html_content)
    analysis["contact_info"] = contacts
    
    if contacts["email_addresses"]:
        print(f"  ✓ Email addresses found: {len(contacts['email_addresses'])}")
        for email in contacts["email_addresses"]:
            print(f"    • {email}")
    else:
        print(f"  ✗ No email addresses found")
    
    if contacts["phone_numbers"]:
        print(f"\n  ✓ Phone numbers found: {len(contacts['phone_numbers'])}")
        for phone in contacts["phone_numbers"][:3]:
            print(f"    • {phone}")
    else:
        print(f"\n  ✗ No phone numbers found")
    
    if contacts["external_links"]:
        print(f"\n  ✓ External links found: {len(contacts['external_links'])}")
        for link in contacts["external_links"][:5]:
            print(f"    • {link}")
    
    if contacts["documents"]:
        print(f"\n  ✓ Documents/files referenced: {len(contacts['documents'])}")
        for doc in contacts["documents"][:5]:
            print(f"    • {doc}")
    else:
        print(f"\n  ✗ No document references found")
    
    # 5. Specific Block Detection (Shell Content)
    print("\n[5] SPECIAL BLOCKS & CONTENT VERIFICATION")
    print("─" * 40)
    
    # Check for shell markers and special blocks
    shell_markers = {
        "corporate-details-block": "<!-- CORPORATE DETAILS SHELL -->",
        "filings-index": "<!-- REQUIRED FILING INDEX -->",
        "press-archive": "<!-- REQUIRED PRESS ARCHIVE -->",
        "ir-editable-content": "ir-editable-content"
    }
    
    for marker_name, marker_text in shell_markers.items():
        if marker_text in html_content:
            print(f"  ✓ {marker_name}: Detected")
        elif marker_name in requirements.get('required_blocks', []):
            print(f"  ✗ {marker_name}: REQUIRED but NOT FOUND")
        else:
            print(f"  - {marker_name}: Not present (optional)")
    
    # 6. Summary Issues
    print("\n[6] ISSUES & RECOMMENDATIONS")
    print("─" * 40)
    
    issues = []
    
    if not img_info["present"]:
        issues.append("Missing hero/featured image - should be added for visual appeal")
    
    if char_count < 1000:
        issues.append(f"Content is thin ({char_count} chars) - consider adding more detail")
    
    if not contacts["email_addresses"] and page_id == 5668:
        issues.append("Missing contact email - should have IR contact email")
    
    if "filings-index" in requirements.get('required_blocks', []):
        if "<!-- REQUIRED FILING INDEX -->" not in html_content:
            issues.append("Missing required filing index block - verify shell injection")
    
    if "press-archive" in requirements.get('required_blocks', []):
        if "<!-- REQUIRED PRESS ARCHIVE -->" not in html_content:
            issues.append("Missing required press archive block - verify shell injection")
    
    if issues:
        for i, issue in enumerate(issues, 1):
            print(f"  {i}. {issue}")
    else:
        print(f"  ✓ No major issues found")
    
    analysis["issues"] = issues
    
    return analysis

def main():
    print("\n" + "="*80)
    print("DETAILED INVESTOR PAGE CONTENT ANALYSIS")
    print("="*80)
    
    all_analyses = {}
    
    for page_id in [5668, 5651, 5686, 5716]:
        analysis = analyze_page_in_depth(page_id)
        if analysis:
            all_analyses[page_id] = analysis
    
    # Summary
    print(f"\n\n{'='*80}")
    print("OVERALL ASSESSMENT")
    print(f"{'='*80}\n")
    
    total_issues = sum(len(a.get('issues', [])) for a in all_analyses.values())
    
    print(f"Pages Analyzed: {len(all_analyses)}")
    print(f"Total Issues Identified: {total_issues}")
    print()
    
    for page_id, analysis in all_analyses.items():
        issue_count = len(analysis.get('issues', []))
        status = "✓ OK" if issue_count == 0 else f"✗ {issue_count} issue(s)"
        print(f"[{status}] Page {page_id}: {analysis['title']}")
    
    # Save detailed analysis
    import json
    from datetime import datetime
    
    output_file = r"c:\temp\INVESTOR_PAGES_DETAILED_ANALYSIS.json"
    with open(output_file, 'w') as f:
        json.dump(all_analyses, f, indent=2, default=str)
    print(f"\n✓ Detailed analysis saved to: {output_file}")

if __name__ == "__main__":
    main()
