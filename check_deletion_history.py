#!/usr/bin/env python3
import json

print("=== Deletion History ===\n")

try:
    with open('media_ops_results.json') as f:
        data = json.load(f)
    deleted_ids = data.get('deleted_ids', [])
    print(f'Deleted Media IDs: {len(deleted_ids)} total')
    print("First 20 deleted IDs:")
    for did in deleted_ids[:20]:
        print(f'  {did}')
    if len(deleted_ids) > 20:
        print(f'  ... and {len(deleted_ids) - 20} more')
except FileNotFoundError:
    print("No deletion results file found")
    deleted_ids = []

# Check what the deletion plan was
print("\n=== Duplicate Deletion Plan ===\n")
try:
    with open('media_duplicates_file.csv') as f:
        lines = f.readlines()
    print(f'Plan entries: {len(lines)-1}')
    print("Sample entries:")
    for line in lines[1:6]:
        parts = line.strip().split(',')
        if len(parts) >= 3:
            print(f"  ID {parts[0]}: {parts[1][:50]} (to delete)")
except FileNotFoundError:
    print("No duplicate plan found")
