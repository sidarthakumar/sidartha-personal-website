"""Deploy public static files only. Initial WordPress migration is a separate, backed-up operation.

Secrets arrive through runner environment, never shell arguments or repository files.
The SSH private key is fed to ssh-agent over stdin and never written to disk.
"""
import io
import json
import os
import re
import shlex
import subprocess
import tarfile
import tempfile
import urllib.request
from pathlib import Path

def fail(message):
    raise SystemExit(message)

def run(command, *, data=None, env=None):
    result = subprocess.run(command, input=data, stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=env, timeout=180)
    if result.returncode:
        fail(f'Deployment step failed (exit {result.returncode}); no connection details or secret values logged.')
    return result.stdout

revision = os.environ.get('SITE_REVISION', '')
if not re.fullmatch(r'[a-f0-9]{40}', revision): fail('Missing or invalid source revision.')
required = ['HOSTINGER_SSH_HOST', 'HOSTINGER_SSH_PORT', 'HOSTINGER_SSH_USER', 'HOSTINGER_SSH_KEY', 'HOSTINGER_KNOWN_HOSTS', 'HOSTINGER_SITE_ROOT']
if any(not os.environ.get(name) for name in required): fail('Hostinger deployment connection is not configured.')
host, port, user, key, known, root = [os.environ[name] for name in required]
if not re.fullmatch(r'[A-Za-z0-9.-]+', host) or not port.isdigit() or not 1 <= int(port) <= 65535 or not re.fullmatch(r'[A-Za-z0-9_-]+', user): fail('Invalid SSH connection format.')
if not re.fullmatch(r'/home/[A-Za-z0-9_-]+/domains/sidarthakumar\.com', root): fail('Deployment root must be the approved domain directory.')
if not Path('site-dist/index.html').is_file() or not Path('site-dist/.htaccess').is_file(): fail('Verified public output is missing.')
if Path('site-dist/revision.txt').read_text().strip() != revision: fail('Artifact and source revisions do not match.')
for path in Path('site-dist').rglob('*'):
    if path.is_symlink(): fail('Symlinks are not allowed in the public artifact.')
    if path.is_file() and (any(part in {'.git', 'node_modules', 'wp-content', 'src'} for part in path.parts) or path.suffix in {'.php', '.sql', '.key', '.pem', '.env'}): fail('Unexpected private/source file in the public artifact.')

# Do not allow an older workflow to replace a newer main-branch deployment.
request = urllib.request.Request('https://api.github.com/repos/sidarthakumar/sidartha-personal-website/git/ref/heads/main', headers={
    'Authorization': 'Bearer ' + os.environ['GITHUB_TOKEN'], 'Accept': 'application/vnd.github+json',
})
try:
    with urllib.request.urlopen(request, timeout=20) as response: head = json.load(response)['object']['sha']
except Exception: fail('Could not verify the current GitHub main revision.')
if head != revision: fail('This build is superseded by a newer main revision; deploy the current build.')

agent = run(['ssh-agent', '-s']).decode()
agent_env = os.environ.copy()
for name in ['SSH_AUTH_SOCK', 'SSH_AGENT_PID']:
    match = re.search(rf'{name}=([^;]+);', agent)
    if not match: fail('Could not initialize the deployment agent.')
    agent_env[name] = match.group(1)
try:
    run(['ssh-add', '-'], data=key.encode(), env=agent_env)
    # Host public keys are non-secret, but remain temporary and never enter Git.
    with tempfile.TemporaryDirectory() as directory:
        known_file = Path(directory) / 'known_hosts'
        known_file.write_text(known.rstrip() + '\n')
        known_file.chmod(0o600)
        ssh = ['ssh', '-p', port, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes', '-o', 'ConnectTimeout=15', '-o', 'LogLevel=ERROR', '-o', 'UserKnownHostsFile=' + str(known_file), user + '@' + host]
        def remote(script):
            command = 'bash -s -- ' + shlex.quote(root) + ' ' + shlex.quote(revision)
            return run([*ssh, command], data=script.encode(), env=agent_env).decode().strip()
        previous = remote('''set -euo pipefail
root="$1"; revision="$2"
[ "$(cat "$root/.portfolio/initialized" 2>/dev/null)" = "sidarthakumar.com" ]
[ -L "$root/public_html" ]
current="$(readlink -f "$root/public_html")"
case "$current" in "$root/.portfolio/releases/"*) ;; *) exit 21;; esac
previous="${current##*/}"
[[ "$previous" =~ ^[a-f0-9]{40}$ ]]
[ -f "$current/index.html" ]
[ ! -e "$root/.portfolio/releases/$revision" ]
mkdir -m 700 "$root/.portfolio/releases/$revision"
printf '%s' "$previous"
''')
        if not re.fullmatch(r'[a-f0-9]{40}', previous): fail('The initial migration or rollback target has not been verified.')
        archive = io.BytesIO()
        with tarfile.open(fileobj=archive, mode='w:gz') as tar:
            for file in Path('site-dist').rglob('*'):
                if file.is_file(): tar.add(file, arcname=str(file.relative_to('site-dist')), recursive=False)
        target = root + '/.portfolio/releases/' + revision
        run([*ssh, 'tar -xzf - -C ' + shlex.quote(target)], data=archive.getvalue(), env=agent_env)
        remote('''set -euo pipefail
root="$1"; revision="$2"; release="$root/.portfolio/releases/$revision"
[ -f "$release/index.html" ] && [ -f "$release/.htaccess" ]
[ "$(cat "$release/revision.txt")" = "$revision" ]
find "$release" -type d -exec chmod 755 {} +
find "$release" -type f -exec chmod 644 {} +
ln -s "$release" "$root/.public_html-$revision"
mv -Tf "$root/.public_html-$revision" "$root/public_html"
''')
        try:
            result = subprocess.run(['node', 'tools/portfolio/check-live.mjs'], env=os.environ.copy(), stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=240)
            healthy = result.returncode == 0
        except subprocess.TimeoutExpired:
            healthy = False
        if not healthy:
            # Restore code only, and only if this run still owns the live symlink.
            remote('''set -euo pipefail
root="$1"; revision="$2"
[ "$(readlink -f "$root/public_html")" = "$root/.portfolio/releases/$revision" ]
''' + f'previous={shlex.quote(previous)}\n' + '''[ -f "$root/.portfolio/releases/$previous/index.html" ]
ln -s "$root/.portfolio/releases/$previous" "$root/.rollback-$revision"
mv -Tf "$root/.rollback-$revision" "$root/public_html"
''')
            fail('Published verification failed; the previous code release was restored. Investigate before retrying.')
        print('Verified static release published to sidarthakumar.com. Previous releases retained for recovery.')
finally:
    subprocess.run(['ssh-agent', '-k'], env=agent_env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=10)
