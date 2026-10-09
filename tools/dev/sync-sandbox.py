#!/usr/bin/env python3
"""Copy only this theme/plugin's source into the existing local sandbox; no deletion."""
import io
import os
from pathlib import Path
import subprocess
import tarfile

ROOT = Path(__file__).resolve().parents[2]
RUNTIME = Path('/workspace/local-wordpress')
env = os.environ.copy()
for key in ('DOCKER_CONTEXT', 'DOCKER_TLS', 'DOCKER_TLS_VERIFY', 'DOCKER_CERT_PATH'):
    env.pop(key, None)
env['DOCKER_HOST'] = 'unix:///var/run/docker.sock'
check = subprocess.run(
    ['docker', 'compose', 'run', '--rm', '-T', 'cli', 'eval',
     'if (wp_get_environment_type() !== "local" || get_option("home") !== "http://127.0.0.1:8080") { exit(1); }'],
    cwd=RUNTIME, env=env, capture_output=True, timeout=30,
)
if check.returncode:
    raise SystemExit('FAIL: local-only precondition; diagnostic output withheld')
archive = io.BytesIO()
count = 0
with tarfile.open(fileobj=archive, mode='w') as bundle:
    for folder in ('themes/sidartha', 'plugins/sidartha-portfolio'):
        for path in sorted((ROOT / 'wp-content' / folder).rglob('*')):
            if path.is_symlink():
                raise SystemExit('FAIL: source symlinks are not permitted')
            if path.is_file():
                entry = bundle.gettarinfo(path, arcname=str(path.relative_to(ROOT / 'wp-content')))
                entry.mode = 0o644
                with path.open('rb') as source:
                    bundle.addfile(entry, source)
                count += 1
result = subprocess.run(
    ['docker', 'compose', 'exec', '-T', '-u', '0', 'web', 'tar', '--no-same-owner', '-xf', '-', '-C', '/var/www/html/wp-content'],
    cwd=RUNTIME, env=env, input=archive.getvalue(), capture_output=True, timeout=30,
)
if result.returncode:
    raise SystemExit('FAIL: source copy failed; diagnostic output withheld')
print(f'PASS: copied {count} theme/plugin source files to the local sandbox; no files/data deleted')
