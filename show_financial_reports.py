#!/usr/bin/env python3
import urllib.request, json, base64, re

auth = 'i6IM cqLZ vQDC pIRk nKFr g35i'
credentials = base64.b64encode(b'admin:' + auth.encode()).decode()

print("=== FINANCIAL REPORTS ===\n")

# Get financials page
url = 'https://www.loungenie.com/wp-json/wp/v2/pages?slug=financials&_fields=id,content,title'
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
        print(f"URL: https://www.loungenie.com/financials/")
        print(f"Content Length: {len(content)} chars\n")
        
        # Save full content for review
        with open('financials_reports_content.html', 'w', encoding='utf-8') as f:
            f.write(content)
        print("✓ Full content saved to: financials_reports_content.html\n")
        
        print("="*60)
        print("FINANCIAL REPORTS SECTION:")
        print("="*60 + "\n")
        
        # Extract all links (likely to be reports)
        links = re.findall(r'<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]+)</a>', content)
        
        # Filter for document links
        report_types = ['pdf', 'report', 'filing', 'financial', 'statement', 'document', 'disclosure']
        reports = []
        
        for href, link_text in links:
            link_text_lower = link_text.lower()
            href_lower = href.lower()
            
            # Check if this looks like a financial report
            if any(report_type in href_lower or report_type in link_text_lower for report_type in report_types):
                reports.append((link_text.strip(), href))
        
        # Also look for URLs in href attributes without specific text
        all_hrefs = re.findall(r'href=["\']([^"\']*(?:pdf|xlsx|doc|filing|report|financial)[^"\']*)["\']', content)
        
        if reports:
            print(f"Found {len(reports)} Financial Reports/Links:\n")
            for i, (text, url) in enumerate(reports, 1):
                # Clean up text
                text = re.sub(r'<[^>]+>', '', text).strip()
                if text:
                    print(f"{i}. {text}")
                    print(f"   URL: {url}\n")
        
        if all_hrefs:
            print(f"\nAdditional Document Links ({len(all_hrefs)}):\n")
            for i, url in enumerate(all_hrefs[:10], 1):
                # Extract filename
                filename = url.split('/')[-1] if '/' in url else url
                print(f"  • {filename}")
                print(f"    {url}\n")
            if len(all_hrefs) > 10:
                print(f"  ... and {len(all_hrefs) - 10} more files")
        
        # Extract text content with financial keywords
        print("\n" + "="*60)
        print("FINANCIAL SECTIONS:")
        print("="*60 + "\n")
        
        # Look for headings and structure
        headings = re.findall(r'<h[1-4][^>]*>([^<]+)</h[1-4]>', content)
        
        if headings:
            for heading in headings[:10]:
                heading = re.sub(r'<[^>]+>', '', heading).strip()
                if heading and any(kw in heading.lower() for kw in ['financial', 'report', 'statement', 'filing', 'sec']):
                    print(f"📄 {heading}\n")
        
        # Extract first 1500 chars of actual content (after styles)
        print("\n" + "="*60)
        print("PAGE CONTENT PREVIEW:")
        print("="*60 + "\n")
        
        # Find main content section
        content_match = re.search(r'<div[^>]*class="[^"]*content[^"]*"[^>]*>(.+?)</div>', content, re.DOTALL)
        if content_match:
            preview = content_match.group(1)[:1500]
        else:
            # Skip styles and get body content
            style_end = content.find('</style>') + len('</style>')
            preview = content[style_end:style_end+1500]
        
        # Clean HTML for display
        preview = re.sub(r'<script[^>]*>.*?</script>', '', preview, flags=re.DOTALL)
        preview = re.sub(r'<style[^>]*>.*?</style>', '', preview, flags=re.DOTALL)
        preview = re.sub(r'<[^>]+>', '\n', preview)
        preview = re.sub(r'\n\s*\n', '\n', preview)
        
        lines = preview.split('\n')
        meaningful_lines = [line.strip() for line in lines if line.strip() and len(line.strip()) > 10]
        
        print('\n'.join(meaningful_lines[:15]))
        print(f"\n... [Full content saved to financials_reports_content.html]")
        
    else:
        print("Financials page not found")
        
except Exception as e:
    print(f"Error: {e}")

print("\n" + "="*60)
print("TO VIEW ALL FINANCIAL REPORTS:")
print("="*60)
print("""
1. Go to: https://www.loungenie.com/financials/
2. Press: Ctrl+Shift+R (hard refresh to see full content)
3. View all report links and downloadable documents

Reports typically include:
  ✓ SEC Filings (if TSX-V listed)
  ✓ Annual Financial Statements
  ✓ Quarterly Reports (Q1, Q2, Q3, Q4)
  ✓ News Releases with Financial Data
  ✓ Annual Reports
  
Contact: info@poolsafeinc.com
Phone: +1 (416) 630-2444
""")
