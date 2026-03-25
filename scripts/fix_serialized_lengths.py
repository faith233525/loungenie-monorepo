#!/usr/bin/env python3
"""Fix PHP serialized string length counters in a large SQL dump (streaming).

This is a best-effort, streaming fixer that locates patterns like s:123:"...";
and recalculates the length using UTF-8 byte length of the content between
quotes. It supports serialized values that span multiple lines.

Usage:
  python scripts/fix_serialized_lengths.py input.sql output_fixed.sql

Notes and caveats:
- This attempts to find closing '";' to end the serialized string. It may not
  handle exotic cases where the serialized string includes an unescaped '";'
  sequence. Test on a small sample before running on the full 3GB file.
- Always keep backups of the original SQL. Run on a copy.
"""
import sys
import re
from pathlib import Path

if len(sys.argv) != 3:
    print('Usage: fix_serialized_lengths.py <input.sql> <output.sql>')
    sys.exit(2)

input_path = Path(sys.argv[1])
output_path = Path(sys.argv[2])
if not input_path.exists():
    print('Input not found:', input_path)
    sys.exit(3)

# Pattern to find the start of a serialized string: s:<digits>:"    (we capture the prefix)
start_re = re.compile(r's:(\d+):"', re.IGNORECASE)

lines_processed = 0
fixes = 0

# We'll process line-by-line but accumulate across lines while within a serialized string
with input_path.open('r', encoding='utf-8', errors='ignore') as r, output_path.open('w', encoding='utf-8') as w:
    pending_prefix = None  # text before the s:...:" including the 's:(\d+:"' part
    pending_declared = None
    pending_buffer = []  # list of strings accumulated for content and remainder

    for raw_line in r:
        line = raw_line
        lines_processed += 1

        # If not pending, look for start pattern in the line (may be multiple times)
        if pending_prefix is None:
            pos = 0
            out_chunks = []
            while True:
                m = start_re.search(line, pos)
                if not m:
                    out_chunks.append(line[pos:])
                    break
                # append text before match
                out_chunks.append(line[pos:m.start()])
                # set up pending for content after the opening quote
                declared = int(m.group(1))
                # find the index after the opening quote
                qstart = m.end()
                # from qstart, search for closing '";'
                closing_idx = line.find('";', qstart)
                if closing_idx != -1:
                    # serialized string ends on same line
                    content = line[qstart:closing_idx]
                    # compute byte length
                    actual_len = len(content.encode('utf-8'))
                    fixes += 1 if actual_len != declared else 0
                    # reconstruct fixed serialized fragment
                    fixed_fragment = f's:{actual_len}:"{content}";'
                    out_chunks.append(fixed_fragment)
                    pos = closing_idx + 2
                    continue
                else:
                    # needs to accumulate from qstart onward (may be rest of line and following lines)
                    pending_prefix = line[:m.start()]
                    pending_declared = declared
                    pending_buffer = [line[qstart:]]
                    break
            # write assembled output
            w.write(''.join(out_chunks))
        else:
            # Accumulating until we find closing '";'
            pending_buffer.append(line)
            combined = ''.join(pending_buffer)
            closing_idx = combined.find('";')
            if closing_idx != -1:
                content = combined[:closing_idx]
                remainder = combined[closing_idx+2:]
                actual_len = len(content.encode('utf-8'))
                fixes += 1 if actual_len != pending_declared else 0
                fixed_fragment = f's:{actual_len}:"{content}";'
                # write pending_prefix + fixed_fragment + remainder (re-run remainder through processor in case of more patterns)
                # We'll set line = remainder and reset pending, then process remainder by letting it fall through the main loop logic:
                line = pending_prefix + fixed_fragment + remainder
                pending_prefix = None
                pending_declared = None
                pending_buffer = []
                # Now process this reconstructed line: to handle potential multiple serialized in same line,
                # we loop back by setting pos=0 and re-running the 'not pending' branch for this line.
                pos = 0
                out_chunks = []
                while True:
                    m = start_re.search(line, pos)
                    if not m:
                        out_chunks.append(line[pos:])
                        break
                    out_chunks.append(line[pos:m.start()])
                    declared = int(m.group(1))
                    qstart = m.end()
                    closing_idx = line.find('";', qstart)
                    if closing_idx != -1:
                        content = line[qstart:closing_idx]
                        actual_len = len(content.encode('utf-8'))
                        fixes += 1 if actual_len != declared else 0
                        fixed_fragment = f's:{actual_len}:"{content}";'
                        out_chunks.append(fixed_fragment)
                        pos = closing_idx + 2
                        continue
                    else:
                        # enter pending again
                        pending_prefix = line[:m.start()]
                        pending_declared = declared
                        pending_buffer = [line[qstart:]]
                        break
                w.write(''.join(out_chunks))
            else:
                # still pending, continue reading
                pass

        # periodic progress output to stderr
        if lines_processed % 200000 == 0:
            print(f'Processed {lines_processed} lines; fixes so far: {fixes}', file=sys.stderr)

# final status
print(f'Completed. Lines processed: {lines_processed}. Fixes applied: {fixes}.')
print(f'Output written to: {output_path}')
print('IMPORTANT: Test the resulting SQL on a small sample or staging before use.')
