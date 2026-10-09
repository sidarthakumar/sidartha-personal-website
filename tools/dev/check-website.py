#!/usr/bin/env python3
"""Run the PHP integration suite in an ephemeral CLI container, with safe output."""
import os
from pathlib import Path
import subprocess

env = os.environ.copy()
for key in ('DOCKER_CONTEXT', 'DOCKER_TLS', 'DOCKER_TLS_VERIFY', 'DOCKER_CERT_PATH'):
    env.pop(key, None)
env['DOCKER_HOST'] = 'unix:///var/run/docker.sock'
runner = '''set -eu
task3_file=$(mktemp)
trap 'rm -f "$task3_file"' EXIT
cat > "$task3_file"
wp --user=developer eval-file "$task3_file"
'''
result = subprocess.run(
    ['docker', 'compose', 'run', '--rm', '-T', '--entrypoint', 'sh', 'cli', '-c', runner],
    cwd='/workspace/local-wordpress', env=env,
    input=Path(__file__).with_name('test-website.php').read_bytes(),
    capture_output=True, timeout=90,
)
output = result.stdout.decode('utf-8', errors='replace')
for line in output.splitlines():
    if line.startswith(('PASS:', 'Success:')):
        print(line)
if result.returncode or 'website integration/security checks passed.' not in output:
    # Test labels are static source text; show the fixed failure label, no raw PHP diagnostics.
    for line in result.stderr.decode('utf-8', errors='replace').splitlines():
        if line.startswith('Error: FAIL:'):
            print(line)
    raise SystemExit('FAIL: integration suite failed; other diagnostics withheld')
