"""Scan working source and optional full history. Print locations/types, never matched values."""
import argparse
import hashlib
import re
import subprocess
from pathlib import Path

PATTERNS = {
    'private key': rb'-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----',
    'GitHub token': rb'(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,})',
    'AWS access key': rb'AKIA[0-9A-Z]{16}',
    'OpenAI-style key': rb'\bsk-(?:proj-)?[A-Za-z0-9_-]{32,}',
    'Google API key': rb'AIza[0-9A-Za-z_-]{30,}',
    'credential URL': rb'https?://[^\s/:]+:[^\s/@]+@',
    'hardcoded credential': rb'''(?im)(?:DB_PASSWORD|database_password|api_key|secret_key|ftp_password|ssh_password)\s*['"]?\s*[:=,]\s*['"](?!\s*['"]|\$|<|PLACEHOLDER|example|changeme|dummy|test)[^'"\n]{8,}['"]''',
}
parser = argparse.ArgumentParser()
parser.add_argument('--history', action='store_true')
args = parser.parse_args()
findings = set()

def scan(name, data):
    for kind, pattern in PATTERNS.items():
        for match in re.finditer(pattern, data):
            # Reviewed synthetic auth-URL rejection fixture targeting example.invalid.
            # Scope the exception to the precise bytes and historical test file.
            if (name == 'tools/dev/test-website.php' and kind == 'credential URL'
                and hashlib.sha256(match.group()).hexdigest() == '093866188144b0216fb125431572e8ac2df8980dfeeb6ebeb3d9f3987d1e4c1d'):
                continue
            findings.add((name, kind))

def git(*args):
    return subprocess.check_output(['git', *args], stderr=subprocess.DEVNULL)

if args.history:
    objects = git('rev-list', '--objects', '--all').splitlines()
    checked = 0
    for entry in objects:
        sha, _, raw_name = entry.partition(b' ')
        kind = git('cat-file', '-t', sha.decode()).strip()
        if kind != b'blob': continue
        checked += 1
        data = git('cat-file', 'blob', sha.decode())
        name = raw_name.decode(errors='replace') or sha.decode()
        scan(name, data)
        if name.endswith('.zip'):
            import io, zipfile
            with zipfile.ZipFile(io.BytesIO(data)) as archive:
                for member in archive.infolist():
                    if not member.is_dir() and member.file_size < 2000000:
                        scan(name + ':' + member.filename, archive.read(member))
    print(f'History scan checked {checked} unique blobs and embedded release ZIP members.')
else:
    excluded = {'.git', 'node_modules', '.astro', 'site-dist', 'dist', '__pycache__'}
    checked = 0
    for file in Path('.').rglob('*'):
        if not file.is_file() or any(p in excluded for p in file.parts): continue
        if file.stat().st_size > 2000000: continue
        checked += 1
        scan(str(file), file.read_bytes())
    print(f'Source scan checked {checked} files.')

if findings:
    for name, kind in sorted(findings): print(f'REVIEW: {name} ({kind})')
    raise SystemExit(1)
print('No matches for configured secret patterns. This scan is not a guarantee against all secret formats.')
