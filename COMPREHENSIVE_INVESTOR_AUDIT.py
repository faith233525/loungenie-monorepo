#!/usr/bin/env python3
"""
COMPREHENSIVE INVESTOR PAGE AUDIT
Audits all investor-related pages (IDs: 5668, 5651, 5686, 5716)
for structure, content completeness, links, and accessibility
"""

import requests
import json
import re
from urllib.parse import urljoin, urlparse
from html.parser import HTMLParser
from datetime import datetime
import sys

# Configuration
STAGING_URL = "https://loungenie.com/staging"
WP_API_URL = f"{STAGING_URL}/wp-json/wp/v2"
AUTH = ("Copilot", "U7GM Z9qE QOq6 MQva IzcQ 6PU2")

# Investor page IDs to audit
INVESTOR_PAGES = {
    5668: "Main Investor Page",
    5651: "Investor Page 5651",
    5686: "Financials",
    5716: "Press"
}

# Expected content sections by page type
REQUIRED_SECTIONS = {
    5668: ["corporate", "governance", "financials", "press", "compliance", "contact", "transfer"],
    5651: ["overview", "governance"],
    5686: ["filings", "reports"],
    5716: ["press", "archive"]
}

class LinkExtractor(HTMLParser):
    """Extract all links from HTML content"""
    def __init__(self):
        super().__init__()
        self.links = []
        self.images = []
        self.headings = {"h1": [], "h2": [], "h3": [], "h4": [], "h5": [], "h6": []}
        self.current_tag = None

    def handle_starttag(self, tag, attrs):
        attrs_dict = dict(attrs)
        if tag == 'a' and 'href' in attrs_dict:
            self.links.append(attrs_dict['href'])
        elif tag == 'img':
            alt_text = attrs_dict.get('alt', '')
            src = attrs_dict.get('src', '')
            self.images.append({"src": src, "alt": alt_text, "has_alt": bool(alt_text)})
        elif tag in ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']:
            self.current_tag = tag
    
    def handle_data(self, data):
        if self.current_tag and data.strip():
            self.headings[self.current_tag].append(data.strip())
    
    def handle_endtag(self, tag):
        if tag == self.current_tag:
            self.current_tag = None

def validate_url(url_string, base_url):
    """Check if URL is valid and accessible"""
    # Handle relative URLs
    if url_string.startswith('/'):
        full_url = urljoin(base_url, url_string)
    elif url_string.startswith('http'):
        full_url = url_string
    else:
        return None
    
    return full_url

def check_link_status(url, timeout=5):
    """Check if a link returns a valid status code"""
    try:
        response = requests.head(url, timeout=timeout, allow_redirects=True, verify=False)
        return response.status_code, response.status_code < 400
    except requests.exceptions.Timeout:
        return "Timeout", False
    except requests.exceptions.ConnectionError:
        return "Connection Error", False
    except Exception as e:
        return str(e), False

def analyze_heading_hierarchy(headings):
    """Check if heading hierarchy is proper"""
    hierarchy_issues = []
    tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']
    
    # Count H1s - should be 1
    h1_count = len(headings.get('h1', []))
    if h1_count == 0:
        hierarchy_issues.append("⚠ No H1 heading found")
    elif h1_count > 1:
        hierarchy_issues.append(f"⚠ Multiple H1 headings found ({h1_count}) - should be 1")
    
    # Check for proper nesting
    found_tags = [tag for tag in tags if headings.get(tag, [])]
    if found_tags and found_tags[0] != 'h1' and found_tags[0] != 'h2':
        hierarchy_issues.append(f"⚠ Improper heading start: {found_tags[0]} (should start with H1 or H2)")
    
    return hierarchy_issues

def audit_page(page_id, page_title):
    """Comprehensive audit of a single investor page"""
    print(f"\n{'='*80}")
    print(f"AUDITING PAGE {page_id}: {page_title}")
    print(f"{'='*80}\n")
    
    audit_report = {
        "page_id": page_id,
        "title": page_title,
        "timestamp": datetime.now().isoformat(),
        "findings": {
            "basic_info": {},
            "content_structure": {},
            "links": {},
            "images": {},
            "accessibility": {},
            "quality": {}
        },
        "issues": []
    }
    
    try:
        # TASK 1: Fetch page basic info
        print(f"[1/5] Verifying page setup...")
        url = f"{WP_API_URL}/pages/{page_id}?context=edit"
        response = requests.get(url, auth=AUTH, verify=False)
        response.raise_for_status()
        page_data = response.json()
        
        basic_info = {
            "title": page_data.get('title', {}).get('rendered', 'N/A'),
            "slug": page_data.get('slug', 'N/A'),
            "status": page_data.get('status', 'N/A'),
            "url": page_data.get('link', 'N/A'),
            "featured_image_id": page_data.get('featured_media', 0),
            "last_modified": page_data.get('modified', 'N/A'),
            "author": page_data.get('author', 'N/A')
        }
        
        audit_report["findings"]["basic_info"] = basic_info
        
        print(f"  ✓ Title: {basic_info['title']}")
        print(f"  ✓ URL: {basic_info['url']}")
        print(f"  ✓ Status: {basic_info['status']}")
        
        if basic_info['featured_image_id'] > 0:
            print(f"  ✓ Hero Image: Present (ID {basic_info['featured_image_id']})")
        else:
            print(f"  ✗ Hero Image: MISSING")
            audit_report["issues"].append("Missing hero/featured image")
        
        # TASK 2: Parse content structure
        print(f"\n[2/5] Analyzing content structure...")
        
        html_content = page_data.get('content', {}).get('rendered', '')
        raw_content = page_data.get('content', {}).get('raw', '')
        
        if not html_content:
            print(f"  ✗ No content found")
            audit_report["issues"].append("Page has no content")
            audit_report["findings"]["content_structure"]["has_content"] = False
        else:
            print(f"  ✓ Content present ({len(html_content)} characters)")
            audit_report["findings"]["content_structure"]["has_content"] = True
            audit_report["findings"]["content_structure"]["content_length"] = len(html_content)
            
            # Extract sections and structure
            parser = LinkExtractor()
            parser.feed(html_content)
            
            # Check for Gutenberg blocks
            has_gutenberg_blocks = '<!-- wp:' in html_content
            print(f"  {'✓' if has_gutenberg_blocks else '✗'} Gutenberg Blocks: {'Present' if has_gutenberg_blocks else 'Not detected'}")
            
            # Analyze sections
            sections = re.findall(r'wp:heading[^>]*>.*?<h\d[^>]*>([^<]+)</h\d>', html_content)
            if sections:
                print(f"  ✓ Sections found: {len(sections)}")
                audit_report["findings"]["content_structure"]["sections"] = sections
            
            # Check for required content elements based on page ID
            content_checks = {
                5668: {
                    "company info": ["company", "information", "about", "corporate"],
                    "board/governance": ["board", "governance", "directors", "management"],
                    "financials": ["financial", "revenue", "profit", "earnings"],
                    "press": ["press", "news", "release", "announcement"],
                    "compliance": ["compliance", "sec", "sedar", "filing"],
                    "contact": ["contact", "investor relations", "email", "phone"]
                },
                5651: {"overview": ["overview", "about", "information"]},
                5686: {"filings": ["filing", "form", "report", "annual"]},
                5716: {"press": ["press", "release", "archive"]}
            }
            
            page_checks = content_checks.get(page_id, {})
            for content_type, keywords in page_checks.items():
                content_lower = html_content.lower()
                found = any(keyword in content_lower for keyword in keywords)
                status = "✓" if found else "✗"
                print(f"  {status} {content_type.title()}: {'Present' if found else 'NOT FOUND'}")
                if not found:
                    audit_report["issues"].append(f"Missing or unclear: {content_type}")
        
        # TASK 3: Extract and audit links
        print(f"\n[3/5] Auditing hyperlinks...")
        
        parser = LinkExtractor()
        parser.feed(html_content)
        
        links = parser.links
        unique_links = list(set(links))
        
        print(f"  ✓ Total links found: {len(links)}")
        print(f"  ✓ Unique links: {len(unique_links)}")
        
        audit_report["findings"]["links"]["total_found"] = len(links)
        audit_report["findings"]["links"]["unique"] = len(unique_links)
        audit_report["findings"]["links"]["link_list"] = unique_links[:20]  # First 20 for report
        
        # Check for common broken patterns
        broken_patterns = ['#', 'javascript:', 'mailto:', 'placeholder', 'example.com']
        suspicious_links = [link for link in unique_links if any(pattern in link for pattern in broken_patterns)]
        
        if suspicious_links:
            print(f"  ⚠ Potentially problematic links: {len(suspicious_links)}")
            for link in suspicious_links[:5]:
                print(f"    - {link}")
            audit_report["issues"].append(f"Found {len(suspicious_links)} potentially broken links")
        
        # Sample check a few external links
        external_links = [l for l in unique_links if l.startswith('http') and 'loungenie' not in l]
        if external_links:
            print(f"\n  Checking {min(3, len(external_links))} external links...")
            for i, link in enumerate(external_links[:3]):
                status_code, is_valid = check_link_status(link)
                status = "✓" if is_valid else "✗"
                print(f"    {status} {link[:60]}... [{status_code}]")
        
        # TASK 4: Image audit
        print(f"\n[4/5] Auditing images & accessibility...")
        
        images = parser.images
        print(f"  ✓ Images found: {len(images)}")
        audit_report["findings"]["images"]["total"] = len(images)
        
        images_without_alt = [img for img in images if not img['has_alt']]
        if images_without_alt:
            print(f"  ✗ Images without alt text: {len(images_without_alt)}")
            audit_report["issues"].append(f"Images missing alt text: {len(images_without_alt)}")
            for img in images_without_alt[:3]:
                print(f"    - {img['src'][:60]}")
        else:
            print(f"  ✓ All images have alt text")
        
        audit_report["findings"]["images"]["without_alt_text"] = len(images_without_alt)
        
        # TASK 5: Heading hierarchy
        print(f"\n[5/5] Checking heading hierarchy...")
        
        headings = parser.headings
        h_count = sum(len(v) for v in headings.values())
        print(f"  ✓ Total headings: {h_count}")
        
        for level in ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']:
            if headings[level]:
                print(f"    {level.upper()}: {len(headings[level])} - {', '.join(headings[level][:2])}{'...' if len(headings[level]) > 2 else ''}")
        
        hierarchy_issues = analyze_heading_hierarchy(headings)
        for issue in hierarchy_issues:
            print(f"  {issue}")
            audit_report["issues"].append(issue)
        
        audit_report["findings"]["accessibility"]["heading_hierarchy"] = headings
        
        # Text readability check (basic)
        text_length = len(re.sub(r'<[^>]+>', '', html_content))
        audit_report["findings"]["quality"]["text_length"] = text_length
        if text_length < 500:
            print(f"\n  ⚠ Minimal content: Only {text_length} characters of text")
            audit_report["issues"].append(f"Page appears thin on content ({text_length} chars)")
        else:
            print(f"\n  ✓ Substantial content: {text_length} characters of text")
        
    except requests.exceptions.RequestException as e:
        print(f"  ✗ API Error: {str(e)}")
        audit_report["findings"]["error"] = str(e)
        audit_report["issues"].append(f"Failed to fetch page: {str(e)}")
    
    # Summary
    print(f"\n{'─'*80}")
    print(f"ISSUES FOUND: {len(audit_report['issues'])}")
    for i, issue in enumerate(audit_report['issues'], 1):
        print(f"  {i}. {issue}")
    print(f"{'─'*80}")
    
    return audit_report

def main():
    print("\n" + "="*80)
    print("COMPREHENSIVE INVESTOR PAGE AUDIT - LounGenie Staging")
    print(f"Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("="*80)
    
    all_reports = []
    
    # Audit each page
    for page_id, page_title in INVESTOR_PAGES.items():
        report = audit_page(page_id, page_title)
        all_reports.append(report)
    
    # Generate summary report
    print(f"\n\n{'='*80}")
    print("AUDIT SUMMARY")
    print(f"{'='*80}\n")
    
    total_issues = sum(len(r['issues']) for r in all_reports)
    pages_with_issues = len([r for r in all_reports if r['issues']])
    
    print(f"Pages Audited: {len(all_reports)}")
    print(f"Pages with Issues: {pages_with_issues}")
    print(f"Total Issues Found: {total_issues}")
    print()
    
    for report in all_reports:
        status = "✓ PASS" if not report['issues'] else "✗ ISSUES"
        print(f"[{status}] Page {report['page_id']}: {len(report['issues'])} issues")
    
    # Save detailed report to JSON
    report_file = r"c:\temp\INVESTOR_AUDIT_COMPREHENSIVE.json"
    with open(report_file, 'w') as f:
        json.dump(all_reports, f, indent=2, default=str)
    print(f"\n✓ Detailed report saved to: {report_file}")
    
    # Generate markdown summary
    md_file = r"c:\temp\INVESTOR_AUDIT_SUMMARY.md"
    with open(md_file, 'w') as f:
        f.write("# LounGenie Investor Pages - Comprehensive Audit\n\n")
        f.write(f"**Date**: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n")
        f.write(f"## Executive Summary\n\n")
        f.write(f"- **Total Pages**: {len(all_reports)}\n")
        f.write(f"- **Issues Found**: {total_issues}\n")
        f.write(f"- **Pages with Issues**: {pages_with_issues}\n\n")
        
        f.write("## Page Audit Details\n\n")
        
        for report in all_reports:
            f.write(f"### Page {report['page_id']}\n\n")
            
            basic = report['findings'].get('basic_info', {})
            f.write(f"**Title**: {basic.get('title', 'N/A')}\n\n")
            f.write(f"**URL**: {basic.get('url', 'N/A')}\n\n")
            f.write(f"**Status**: {basic.get('status', 'N/A')}\n\n")
            
            if report['issues']:
                f.write(f"**Issues** ({len(report['issues'])})\n\n")
                for issue in report['issues']:
                    f.write(f"- {issue}\n")
            else:
                f.write("**Status**: ✓ No issues found\n\n")
            
            f.write("\n---\n\n")
    
    print(f"✓ Summary report saved to: {md_file}\n")

if __name__ == "__main__":
    main()
