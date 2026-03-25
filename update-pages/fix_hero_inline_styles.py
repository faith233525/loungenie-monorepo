import urllib.request, json, base64, re, html as htmllib
from collections import defaultdict

cred = base64.b64encode(b"admin:i6IM cqLZ vQDC pIRk nKFr g35i").decode()
hdrs = {"Authorization": "Basic " + cred, "Content-Type": "application/json"}
base = "https://www.loungenie.com/wp-json/wp/v2"

# ── CONSTANTS ─────────────────────────────────────────────────────────────────
HERO_STYLE = (
    "background:linear-gradient(135deg,#0a2a4a 0%,#1a5276 100%);"
    "color:#fff;"
    "padding:60px 40px;"
    "border-radius:12px;"
    "margin-bottom:40px;"
    "text-align:center;"
)
H1_STYLE = "font-size:36px;font-weight:800;margin:0 0 12px;color:#fff;"
P_STYLE = "font-size:17px;margin:0 auto;opacity:.85;max-width:680px;color:#fff;"

# ── FINANCIALS (5686) ──────────────────────────────────────────────────────────
def build_financials():
    # Fetch original structured revision
    req = urllib.request.Request(f"{base}/pages/5686/revisions/5693", headers=hdrs)
    with urllib.request.urlopen(req) as r:
        rev5693 = json.loads(r.read())
    orig = rev5693["content"]["rendered"]

    # Parse sections
    sections_orig = re.split(r'(<h2[^>]*>.*?</h2>)', orig, flags=re.IGNORECASE|re.DOTALL)
    section_docs = {}
    current_title = None
    for part in sections_orig:
        if re.match(r'<h2', part, re.IGNORECASE):
            current_title = htmllib.unescape(re.sub(r'<[^>]+>', '', part).strip())
            section_docs[current_title] = []
        elif current_title:
            links = re.findall(
                r'<a[^>]+href=["\']([^"\']+\.pdf[^"\']*)["\'][^>]*>(.*?)</a>',
                part, re.IGNORECASE|re.DOTALL
            )
            for url, anchor_raw in links:
                anchor = htmllib.unescape(re.sub(r'<[^>]+>', '', anchor_raw).strip())
                # Fix truncated label
                if "September 30, 202" in anchor and not "2024" in anchor and not "2025" in anchor:
                    anchor = anchor.replace("September 30, 202", "September 30, 2024")
                section_docs[current_title].append((anchor, url))

    # Add 2026 AGM
    section_docs["2026 Annual General Meeting"] = [
        ("Notice of Meeting & Management Information Circular",
         "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Notice-of-Meeting-Combined-with-MIC.pdf"),
        ("Form of Proxy",
         "https://www.loungenie.com/wp-content/uploads/2026/03/Pool-Safe-Form-of-Proxy_Common-Shares-Final.pdf"),
    ]

    ordered = [
        "2026 Annual General Meeting",
        "2025 Financial Reports",
        "2025 Annual General Meeting",
        "2024 Financial Reports",
        "2023 Financial Reports",
        "2022 Financial Reports",
        "2021 Financial Reports",
        "2020 Financial Reports",
        "2019 Financial Reports",
        "2018 Financial Reports",
        "2017 Financial Reports",
        "2016 Financial Reports",
    ]

    def make_section(title, docs):
        rows = ""
        for anchor, url in docs:
            rows += (
                f'<tr>'
                f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;">'
                f'<a href="{url}" target="_blank" rel="noopener noreferrer" style="color:#1a73e8;text-decoration:none;font-weight:500;">{anchor}</a>'
                f'</td>'
                f'<td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;text-align:center;white-space:nowrap;">'
                f'<a href="{url}" target="_blank" rel="noopener noreferrer" style="background:#1a73e8;color:#fff;padding:4px 14px;border-radius:4px;text-decoration:none;font-size:13px;">Download PDF</a>'
                f'</td>'
                f'</tr>'
            )
        return (
            f'<h2 style="background:#0a2a4a;color:#fff;padding:10px 20px;margin:36px 0 0;'
            f'border-radius:6px 6px 0 0;font-size:15px;font-weight:700;">{title}</h2>'
            f'<table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #dde3ea;'
            f'border-top:none;border-radius:0 0 6px 6px;margin-bottom:4px;">'
            f'<thead><tr style="background:#f0f4f8;">'
            f'<th style="padding:9px 16px;text-align:left;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;">Document</th>'
            f'<th style="padding:9px 16px;text-align:center;font-size:13px;color:#555;font-weight:600;border-bottom:2px solid #dde3ea;width:130px;">PDF</th>'
            f'</tr></thead>'
            f'<tbody>{rows}</tbody></table>'
        )

    sections_html = "".join(make_section(t, section_docs.get(t, [])) for t in ordered if section_docs.get(t))
    total = sum(len(v) for v in section_docs.values())

    wrap_style = "max-width:960px;margin:0 auto;padding:0 20px 60px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;"
    intro_style = "background:#f8f9fa;border-left:4px solid #1a73e8;padding:18px 24px;border-radius:0 8px 8px 0;margin-bottom:8px;font-size:15px;color:#444;"

    return (
        f'<!-- wp:html -->'
        f'<div style="{wrap_style}">'
        f'<div style="{HERO_STYLE}">'
        f'<h1 style="{H1_STYLE}">Investor Information &mdash;<br>The LounGenie &amp; Pool Safe Inc.</h1>'
        f'<p style="{P_STYLE}">Financial statements, MD&amp;A reports, and AGM filings for Pool-Safe Innovations Inc. All filings as submitted to SEDAR.</p>'
        f'</div>'
        f'<h2 style="font-size:22px;color:#0a2a4a;margin:0 0 8px;font-weight:700;">Financial Reports</h2>'
        f'<div style="{intro_style}"><strong>Total filings: {total}</strong> &mdash; Quarterly statements and MD&amp;A from 2016&ndash;2025 plus Annual General Meeting documents.</div>'
        f'{sections_html}'
        f'<p style="text-align:center;color:#888;font-size:13px;margin-top:40px;">Pool-Safe Innovations Inc. &bull; All filings as submitted to SEDAR &bull; Documents open in a new tab</p>'
        f'</div>'
        f'<!-- /wp:html -->'
    )


# ── PRESS (5716) ──────────────────────────────────────────────────────────────
def build_press():
    req = urllib.request.Request(f"{base}/pages/5716/revisions/8630", headers=hdrs)
    with urllib.request.urlopen(req) as r:
        rev = json.loads(r.read())
    original = rev["content"]["rendered"]

    # Strip existing h1 and intro p
    h1m = re.search(r'^<h1[^>]*>.*?</h1>', original, re.IGNORECASE|re.DOTALL)
    body = original
    if h1m:
        rest = original[h1m.end():].lstrip()
        pm = re.match(r'<p[^>]*text-align[^>]*>.*?</p>', rest, re.IGNORECASE|re.DOTALL)
        body = rest[pm.end():].strip() if pm else rest.strip()

    # Fix any h2 tags in body to use inline styles (dark heading style)
    def style_h2(m):
        inner = re.sub(r'<[^>]+>', '', m.group(1)).strip()
        return (
            f'<h2 style="font-size:14px;font-weight:700;color:#0a2a4a;'
            f'border-bottom:2px solid #e8ecf0;padding-bottom:8px;margin:32px 0 8px;'
            f'text-transform:uppercase;letter-spacing:.3px;">{inner}</h2>'
        )
    body = re.sub(r'<h2[^>]*>(.*?)</h2>', style_h2, body, flags=re.IGNORECASE|re.DOTALL)

    # Style paragraphs and links inline
    body = re.sub(r'<p(?![^>]*style)', '<p style="font-size:15px;line-height:1.65;color:#333;margin:0 0 12px;"', body)
    body = re.sub(
        r'<a\s+href=',
        '<a style="color:#1a73e8;text-decoration:none;font-weight:500;" href=',
        body
    )

    wrap_style = "max-width:900px;margin:0 auto;padding:0 20px 60px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;"

    return (
        f'<!-- wp:html -->'
        f'<div style="{wrap_style}">'
        f'<div style="{HERO_STYLE}">'
        f'<h1 style="{H1_STYLE}">&#128240; Press Releases</h1>'
        f'<p style="{P_STYLE}">Stay informed on LounGenie\'s newest partnerships, product launches, and corporate milestones.</p>'
        f'</div>'
        f'{body}'
        f'</div>'
        f'<!-- /wp:html -->'
    )


# ── BOARD (5651) ──────────────────────────────────────────────────────────────
def build_board():
    req = urllib.request.Request(f"{base}/pages/5651", headers=hdrs)
    with urllib.request.urlopen(req) as r:
        page = json.loads(r.read())
    content = page["content"]["rendered"]

    # Replace existing hero div (class-based) with inline-styled version
    # Find the hero div and replace its style attribute
    content = re.sub(
        r'<div\s+class=["\']board-hero["\'][^>]*>',
        f'<div style="{HERO_STYLE}">',
        content, flags=re.IGNORECASE
    )
    content = re.sub(
        r'<div\s+class=["\']fin-hero["\'][^>]*>',
        f'<div style="{HERO_STYLE}">',
        content, flags=re.IGNORECASE
    )
    content = re.sub(
        r'<div\s+class=["\']press-hero["\'][^>]*>',
        f'<div style="{HERO_STYLE}">',
        content, flags=re.IGNORECASE
    )
    # Fix h1 inside hero
    content = re.sub(
        r'(<div style="background:linear-gradient[^"]*">)\s*<h1(?![^>]*style)',
        r'\1<h1 style="' + H1_STYLE + '"',
        content, flags=re.IGNORECASE
    )
    # Fix p inside hero
    content = re.sub(
        r'(background:linear-gradient[^<]*)<p(?![^>]*style)',
        r'\1<p style="' + P_STYLE + '"',
        content, flags=re.IGNORECASE
    )
    return content


# ── PUSH ALL THREE ─────────────────────────────────────────────────────────────
pages_to_fix = [
    (5686, "Financials", build_financials),
    (5716, "Press",      build_press),
    (5651, "Board",      build_board),
]

for page_id, label, builder in pages_to_fix:
    print(f"Building {label}...", end=" ", flush=True)
    new_content = builder()
    print(f"{len(new_content)} chars...", end=" ", flush=True)
    payload = json.dumps({"content": new_content}).encode()
    req2 = urllib.request.Request(f"{base}/pages/{page_id}", data=payload, headers=hdrs, method="POST")
    with urllib.request.urlopen(req2) as r:
        result = json.loads(r.read())
    print(f"done! ({len(result['content']['rendered'])} rendered)")

print("\nAll pages updated with inline styles.")
