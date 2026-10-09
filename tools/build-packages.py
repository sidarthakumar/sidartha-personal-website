#!/usr/bin/env python3
"""Reproducible, source-only WordPress ZIPs. Never archive the workspace/runtime."""
from pathlib import Path
import hashlib
import json
import zipfile

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / 'dist'
OUTPUT.mkdir(exist_ok=True)
PACKAGES = {
    'sidartha-1.0.0.zip': ROOT / 'wp-content/themes/sidartha',
    'sidartha-portfolio-1.0.0.zip': ROOT / 'wp-content/plugins/sidartha-portfolio',
}
allowed = {'.php', '.css', '.js', '.json', '.txt', '.png'}
manifest = {}
for filename, folder in PACKAGES.items():
    target = OUTPUT / filename
    with zipfile.ZipFile(target, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for source in sorted(folder.rglob('*')):
            if source.is_symlink():
                raise SystemExit('Refusing a source symlink')
            if not source.is_file():
                continue
            if source.suffix not in allowed or any(part.startswith('.') for part in source.relative_to(folder).parts):
                raise SystemExit('Refusing an unapproved package member')
            name = str(source.relative_to(folder.parent))
            info = zipfile.ZipInfo(name, date_time=(2026, 10, 9, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, source.read_bytes())
    with zipfile.ZipFile(target) as archive:
        if archive.testzip() is not None:
            raise SystemExit('ZIP integrity check failed')
        for name in archive.namelist():
            member = Path(name)
            if member.is_absolute() or '..' in member.parts or member.parts[0] != folder.name:
                raise SystemExit('Unsafe or incorrect ZIP root')
            if archive.read(name) != (folder.parent / member).read_bytes():
                raise SystemExit('Package source mismatch')
        count = len(archive.namelist())
    manifest[filename] = {'sha256': hashlib.sha256(target.read_bytes()).hexdigest(), 'bytes': target.stat().st_size, 'files': count}
    print(f'PASS {filename}: {count} source/assets files, valid WordPress root, contents match source')
(OUTPUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
