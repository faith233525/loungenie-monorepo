#!/usr/bin/env python3
"""Fix alt text for media items that have bad/missing/filename alt text."""
import requests, base64, json, re

creds = base64.b64encode('admin:i6IM cqLZ vQDC pIRk nKFr g35i'.encode()).decode()
hdrs = {'Authorization': f'Basic {creds}', 'Content-Type': 'application/json'}
BASE = 'https://www.loungenie.com/wp-json/wp/v2'

# Map of media IDs → proper alt text for images with bad alt text
# Only fixing items confirmed to have bad alt in our inventory
fixes = {
    # The-Grove series — has alt "The Grove 2" / "The Grove 6" (just partial filenames)
    8337: "LounGenie smart cabana system installed at The Grove Resort Orlando pool area",
    8335: "The Grove Resort pool cabana equipped with LounGenie amenity unit — side profile",
    # Numeric filename alt text (just numbers, useless)
    8367: "Cowabunga Bay water park poolside cabana with LounGenie waterproof safe installed",
    8370: "Resort daybed seating area with LounGenie smart amenity units deployed at waterpark",
    # Generic/placeholder alt text
    8371: "LounGenie poolside amenity unit deployed in cabana — USB charging and waterproof safe",
    8366: "LounGenie poolside installation at resort — side view of unit in pool cabana",
    8365: "LounGenie poolside installation — wide view of cabana area at resort property",
    # Media with raw filenames as alt
    8364: "LounGenie installed inside resort pool area — cabana amenity technology in action",
    8363: "LounGenie smart system inside waterpark cabana — USB charging and safe visible",
    # Hilton Wakoloa alt "Hilton Wakoloa Village 2018 10 Aloha Fal..." — too verbose/technical
    8378: "Hilton Waikoloa Village Aloha Falls cabana with LounGenie STASH, CHARGE, and CHILL installed",
    # Lock images with plain "IMG_20xx" alt
    8688: "LounGenie waterproof safe and lock panel — STASH detail view from active resort installation",
    8687: "LounGenie STASH safe and CHARGE panel — lock detail at poolside cabana",
    8686: "LounGenie waterproof keypad and safe door — service-side view in pool cabana",
    8685: "LounGenie lock panel and safe door — close-up from active LounGenie deployment",
    8678: "LounGenie waterproof safe — portrait lock detail showing keypad entry and door",
    8677: "LounGenie STASH safe door and waterproof keypad — cabana installation detail",
    8676: "LounGenie lock hardware profile — waterproof keypad, safe door, and aluminum body",
    # Hilton Aloha Falls Cabana vertical (alt: "LounGenie unit installed in beachfront resort caba")
    8377: "LounGenie unit installed in beachfront resort cabana — full side profile, Hilton Waikoloa",
    # Westin Hilton Head filename-based
    3588: "LounGenie amenity unit installed at Westin Hilton Head Island Resort and Spa",
    # IMG_9xxx series with generic alt
    3090: "LounGenie smart cabana technology inside resort pool cabana — lock and safe visible",
    3091: "LounGenie smart cabana system at luxury resort — unit framing and panel details",
    3092: "LounGenie amenity unit at upscale poolside cabana — ice bucket and safe installed",
    3093: "LounGenie smart cabana unit installed in luxury resort pool area",
    3094: "Aerial view of resort pool deck featuring LounGenie-equipped cabana row",
    3095: "Luxury poolside setting with LounGenie smart cabana system installed",
    3096: "Resort pool area featuring LounGenie-equipped cabanas — wide angle view",
    3097: "Poolside cabanas equipped with LounGenie amenity units at resort property",
}

print(f"Fixing alt text for {len(fixes)} media items...")
success = 0
failed = 0
for media_id, new_alt in fixes.items():
    r = requests.post(f'{BASE}/media/{media_id}', headers=hdrs,
                      data=json.dumps({'alt_text': new_alt}), timeout=20)
    if r.status_code in (200, 201):
        success += 1
        print(f"  ✓ ID={media_id}: alt updated")
    else:
        failed += 1
        print(f"  ✗ ID={media_id}: HTTP {r.status_code}")

print(f"\nDone: {success} updated, {failed} failed")
