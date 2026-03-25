#!/usr/bin/env python3
"""Fetch the staging homepage HTML for inspection."""
import requests
from pathlib import Path

URL = 'https://loungenie.com/staging/'
OUT = Path('logs/homepage_html.txt')
OUT.parent.mkdir(exist_ok=True)

def main():
    r = requests.get(URL, timeout=20)
    OUT.write_text(r.text, encoding='utf-8')
    print('WROTE', OUT)

if __name__ == '__main__':
    main()
