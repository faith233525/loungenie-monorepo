#!/usr/bin/env python3
"""Single-command staging QA report for structure, SEO, and image integrity.

Usage:
  python staging_qa_report.py
"""

from __future__ import annotations

import re
import subprocess
import sys
import os
import urllib.error
import urllib.request
from dataclasses import dataclass

import requests

PAGES = [
    ("home", "https://loungenie.com/staging/"),
    ("features", "https://loungenie.com/staging/index.php/poolside-amenity-unit/"),
    ("about", "https://loungenie.com/staging/index.php/hospitality-innovation/"),
    ("contact", "https://loungenie.com/staging/index.php/contact-loungenie/"),
    ("videos", "https://loungenie.com/staging/index.php/loungenie-videos/"),
    ("gallery", "https://loungenie.com/staging/index.php/cabana-installation-photos/"),
    ("investors", "https://loungenie.com/staging/index.php/investors/"),
    ("board", "https://loungenie.com/staging/index.php/board/"),
    ("financials", "https://loungenie.com/staging/index.php/financials/"),
    ("press", "https://loungenie.com/staging/index.php/press/"),
]

UA = {"User-Agent": "Mozilla/5.0"}


@dataclass
class PageMetrics:
    name: str
    url: str
    title: bool
    meta_desc: bool
    canonical: bool
    robots: bool
    schema_count: int
    h1_count: int
    og_title: bool
    og_desc: bool
    og_image: bool
    twitter_card: bool
    img_count: int
    missing_alt: int
    lazy_count: int
    srcset_count: int


def run_preflight() -> bool:
    cmd = [sys.executable, "professional-redesign-v12.py", "--preflight-only"]
    env = dict(os.environ)
    env["PYTHONIOENCODING"] = "utf-8"
    env["PYTHONUTF8"] = "1"
    result = subprocess.run(cmd, capture_output=True, text=True, env=env)
    print("\n=== Preflight Gate ===")
    if result.stdout:
        print(result.stdout.strip())
    if result.stderr:
        print(result.stderr.strip())
    return result.returncode == 0


def collect_page_metrics(name: str, url: str) -> PageMetrics:
    html = requests.get(url, headers=UA, timeout=30).text
    imgs = re.findall(r"<img\b[^>]*>", html, flags=re.I)
    return PageMetrics(
        name=name,
        url=url,
        title=bool(re.search(r"<title>.*?</title>", html, flags=re.I | re.S)),
        meta_desc=bool(re.search(r"<meta[^>]+name=[\"']description[\"'][^>]+content=[\"'][^\"']+", html, flags=re.I)),
        canonical=bool(re.search(r"<link[^>]+rel=[\"']canonical[\"']", html, flags=re.I)),
        robots=bool(re.search(r"<meta[^>]+name=[\"']robots[\"']", html, flags=re.I)),
        schema_count=len(re.findall(r"<script[^>]+type=[\"']application/ld\+json[\"']", html, flags=re.I)),
        h1_count=len(re.findall(r"<h1[^>]*>", html, flags=re.I)),
        og_title=bool(re.search(r"<meta[^>]+property=[\"']og:title[\"']", html, flags=re.I)),
        og_desc=bool(re.search(r"<meta[^>]+property=[\"']og:description[\"']", html, flags=re.I)),
        og_image=bool(re.search(r"<meta[^>]+property=[\"']og:image[\"']", html, flags=re.I)),
        twitter_card=bool(re.search(r"<meta[^>]+name=[\"']twitter:card[\"']", html, flags=re.I)),
        img_count=len(imgs),
        missing_alt=sum(1 for tag in imgs if not re.search(r"\balt=[\"'][^\"']*[\"']", tag, flags=re.I)),
        lazy_count=sum(1 for tag in imgs if re.search(r"\bloading=[\"']lazy[\"']", tag, flags=re.I)),
        srcset_count=sum(1 for tag in imgs if re.search(r"\bsrcset=", tag, flags=re.I)),
    )


def collect_broken_assets() -> list[tuple[str, str, str]]:
    broken: list[tuple[str, str, str]] = []
    for label, page_url in PAGES:
        html = requests.get(page_url, headers=UA, timeout=30).text
        img_src = re.findall(r"<img[^>]+src=[\"']([^\"']+)[\"']", html, flags=re.I)
        bg_urls = re.findall(r"background(?:-image)?:\s*url\([\"']?([^\"')\s]+)", html, flags=re.I)
        for asset in sorted(set(img_src + bg_urls)):
            if asset.startswith("data:"):
                continue
            if asset.startswith("//"):
                asset = "https:" + asset
            elif asset.startswith("/"):
                asset = "https://loungenie.com" + asset
            try:
                req = urllib.request.Request(asset, headers=UA)
                with urllib.request.urlopen(req, timeout=12) as resp:
                    if resp.status != 200:
                        broken.append((label, asset, f"HTTP {resp.status}"))
            except urllib.error.HTTPError as err:
                broken.append((label, asset, f"HTTP {err.code}"))
            except Exception as err:  # noqa: BLE001
                broken.append((label, asset, str(err)[:80]))
    return broken


def main() -> int:
    preflight_ok = run_preflight()

    print("\n=== SEO + Structure Table ===")
    print(
        "name,title,meta_desc,canonical,robots,schema,h1,og_title,og_desc,"
        "og_image,twitter_card,imgs,missing_alt,lazy,srcset"
    )
    metrics = [collect_page_metrics(name, url) for name, url in PAGES]
    for m in metrics:
        print(
            f"{m.name},{int(m.title)},{int(m.meta_desc)},{int(m.canonical)},"
            f"{int(m.robots)},{m.schema_count},{m.h1_count},{int(m.og_title)},"
            f"{int(m.og_desc)},{int(m.og_image)},{int(m.twitter_card)},"
            f"{m.img_count},{m.missing_alt},{m.lazy_count},{m.srcset_count}"
        )

    structure_issues = [m.name for m in metrics if m.h1_count != 1]
    seo_issues = [
        m.name
        for m in metrics
        if not all([m.title, m.meta_desc, m.canonical, m.robots, m.og_title, m.og_desc, m.og_image, m.twitter_card])
    ]
    alt_issues = [m.name for m in metrics if m.missing_alt > 0]

    broken_assets = collect_broken_assets()

    marketing = [m for m in metrics if m.name in {"home", "features", "about", "contact", "videos", "gallery"}]
    exceptional_issues: list[str] = []
    for m in marketing:
        if m.img_count > 0 and m.lazy_count < max(1, m.img_count // 3):
            exceptional_issues.append(f"{m.name}: low lazy-load coverage ({m.lazy_count}/{m.img_count})")
        if m.img_count > 0 and m.srcset_count < max(1, m.img_count // 3):
            exceptional_issues.append(f"{m.name}: low srcset coverage ({m.srcset_count}/{m.img_count})")
        if m.schema_count < 1:
            exceptional_issues.append(f"{m.name}: missing schema")

    print("\n=== Image Integrity ===")
    if broken_assets:
        print(f"Broken assets: {len(broken_assets)}")
        for label, asset, err in broken_assets[:20]:
            print(f"- {label}: {err} -> {asset}")
    else:
        print("Broken assets: 0")

    print("\n=== QA Summary ===")
    print(f"preflight_ok: {preflight_ok}")
    print(f"structure_issues: {structure_issues if structure_issues else 'none'}")
    print(f"seo_issues: {seo_issues if seo_issues else 'none'}")
    print(f"missing_alt_issues: {alt_issues if alt_issues else 'none'}")
    print(f"broken_asset_count: {len(broken_assets)}")

    print("\n=== Exceptional Standard ===")
    if exceptional_issues:
        print("status: FAIL")
        for item in exceptional_issues:
            print(f"- {item}")
    else:
        print("status: PASS")

    all_ok = (
        preflight_ok
        and not structure_issues
        and not seo_issues
        and not alt_issues
        and not broken_assets
        and not exceptional_issues
    )
    print("\nRESULT:", "PASS" if all_ok else "FAIL")
    return 0 if all_ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
