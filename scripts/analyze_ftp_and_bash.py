import re
from pathlib import Path

ALLOWED_IPS = {"192.168.2.37"}


def parse_lastlogin(path):
    ips = []
    for line in Path(path).read_text(encoding='utf-8', errors='ignore').strip().splitlines():
        line = line.strip()
        if not line:
            continue
        # lines like: 208.79.216.2 # 2025-12-08 14:41:12 -0500
        m = re.match(r'([0-9.]+)\s*#\s*(.+)$', line)
        if m:
            ip, ts = m.groups()
            ips.append((ip, ts))
    return ips


SUSPICIOUS_PATTERNS = [
    r"\bwget\b",
    r"\bcurl\b",
    r"\brm\s+-rf\b",
    r"\bchmod\s+777\b",
    r"\badduser\b|\buseradd\b",
    r"\bpasswd\b",
    r"\bopenssl\b",
    r"\bmysql\b",
    r"\bwp\s+plugin\b|\bwp\s+core\b",
    r"\bperl\b|\bpython\b|\bbash\b -c",
    r"base64\s+-d",
]


def analyze_bash(path):
    text = Path(path).read_text(encoding='utf-8', errors='ignore')
    lines = text.strip().splitlines()
    suspects = []
    for i, ln in enumerate(lines[-1000:], 1):
        for p in SUSPICIOUS_PATTERNS:
            if re.search(p, ln, re.IGNORECASE):
                suspects.append((i, ln.strip()))
                break
    return lines, suspects


def main():
    lastlogin_path = Path('artifacts/ftp_lastlogin.txt')
    bash_path = Path('artifacts/ftp_bash_history.txt')

    report = []

    if lastlogin_path.exists():
        entries = parse_lastlogin(lastlogin_path)
        report.append('FTP lastlogin entries:')
        unknown_ips = set()
        for ip, ts in entries:
            mark = '' if ip in ALLOWED_IPS else ' <-- NOT YOUR IP'
            report.append(f'{ip} @ {ts}{mark}')
            if ip not in ALLOWED_IPS:
                unknown_ips.add(ip)
        if unknown_ips:
            report.append('\nIPs not matching allowed set (investigate):')
            for ip in sorted(unknown_ips):
                report.append(ip)
    else:
        report.append('No ftp_lastlogin file found at artifacts/ftp_lastlogin.txt')

    if bash_path.exists():
        lines, suspects = analyze_bash(bash_path)
        report.append('\n.bash_history size (lines): %d' % len(lines))
        if suspects:
            report.append('\nSuspicious bash history entries (sample):')
            for idx, ln in suspects[:50]:
                report.append(f'[{idx}] {ln}')
        else:
            report.append('\nNo obvious suspicious commands found in .bash_history sample')
    else:
        report.append('\nNo .bash_history file found at artifacts/ftp_bash_history.txt')

    out = '\n'.join(report)
    Path('artifacts/ftp_audit_report.txt').write_text(out, encoding='utf-8')
    print('Wrote artifacts/ftp_audit_report.txt')


if __name__ == '__main__':
    main()
