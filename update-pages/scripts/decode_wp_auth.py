#!/usr/bin/env python3
import os, base64

WP_AUTH = os.environ.get('WP_AUTH','')
print('WP_AUTH raw:', WP_AUTH)
compact = WP_AUTH.replace(' ','')
try:
    decoded = base64.b64decode(compact).decode('utf-8')
    print('decoded:', decoded)
except Exception as e:
    print('decode failed:', e)
