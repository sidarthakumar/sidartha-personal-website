#!/usr/bin/env python3
"""Bounded, local-only checks for the existing synthetic WordPress sandbox."""

import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import signal
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from contextlib import contextmanager
from dataclasses import dataclass


RUNTIME = Path('/workspace/local-wordpress')
BASE = 'http://127.0.0.1:8080'
REQUEST_TIMEOUT = 10
DASHBOARD_TIMEOUT = 30
COMMAND_TIMEOUT = 30
SLOW_SECONDS = 10
MAX_BODY = 4 * 1024 * 1024


class SmokeFailure(Exception):
    """Only fixed, credential-free diagnostic text may be used as a message."""


def require(condition, message):
    if not condition:
        raise SmokeFailure(message)


@contextmanager
def deadline(seconds):
    """Bound the entire HTTP operation, including body reads, on this Linux CLI."""
    def expired(_signum, _frame):
        raise TimeoutError()

    old_handler = signal.signal(signal.SIGALRM, expired)
    signal.setitimer(signal.ITIMER_REAL, seconds)
    try:
        yield
    finally:
        signal.setitimer(signal.ITIMER_REAL, 0)
        signal.signal(signal.SIGALRM, old_handler)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        # Examine redirects without following them or sending cookies elsewhere.
        return None


@dataclass
class Response:
    status: int
    headers: object
    body: str


class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            NoRedirect(), urllib.request.HTTPCookieProcessor(self.cookies)
        )

    def logged_in(self):
        return any(c.name.startswith('wordpress_logged_in_') for c in self.cookies)

    def request(self, path, data=None, timeout=REQUEST_TIMEOUT):
        url = urllib.parse.urlsplit(BASE + path)
        require(
            path.startswith('/') and not path.startswith('//')
            and url.scheme == 'http' and url.netloc == '127.0.0.1:8080',
            'request target must be the local sandbox',
        )
        try:
            with deadline(timeout):
                try:
                    response = self.opener.open(BASE + path, data=data, timeout=timeout)
                except urllib.error.HTTPError as error:
                    response = error
                with response:
                    body = response.read(MAX_BODY + 1)
                    require(len(body) <= MAX_BODY, 'response exceeds the body-size limit')
                    return Response(response.code, response.headers, body.decode('utf-8'))
        except (TimeoutError, urllib.error.URLError, OSError, UnicodeError) as error:
            timed_out = isinstance(error, TimeoutError) or isinstance(
                getattr(error, 'reason', None), TimeoutError
            )
            raise SmokeFailure(
                f'HTTP deadline exceeded ({timeout}s)' if timed_out
                else 'HTTP transport or response decoding failed'
            ) from None


def compose(*args):
    env = os.environ.copy()
    for key in ('DOCKER_CONTEXT', 'DOCKER_TLS', 'DOCKER_TLS_VERIFY', 'DOCKER_CERT_PATH'):
        env.pop(key, None)
    env['DOCKER_HOST'] = 'unix:///var/run/docker.sock'
    try:
        result = subprocess.run(
            ['docker', 'compose', *args], cwd=RUNTIME, env=env,
            capture_output=True, text=True, timeout=COMMAND_TIMEOUT,
        )
    except subprocess.TimeoutExpired:
        raise SmokeFailure(f'local command deadline exceeded ({COMMAND_TIMEOUT}s)') from None
    except OSError:
        raise SmokeFailure('local command unavailable') from None
    # Command output can contain private configuration on failure; never echo it.
    require(result.returncode == 0, 'local command failed (output withheld)')
    return result.stdout.strip()


def php_check():
    data = json.loads(compose('exec', '-T', 'web', 'php', '-r',
        'echo json_encode(["version" => PHP_VERSION, "mysqli" => extension_loaded("mysqli")]);'))
    require(data.get('mysqli') is True, 'PHP mysqli extension is unavailable')
    require(re.fullmatch(r'\d+\.\d+\.\d+[\w.+-]*', data['version']), 'unexpected PHP version')
    return 'PHP ' + data['version'] + '; mysqli available'


def mariadb_check():
    output = compose('exec', '-T', 'db', 'mariadb', '--version')
    match = re.search(r'\b(\d+\.\d+\.\d+)-MariaDB\b', output)
    require(match is not None, 'MariaDB client is unavailable')
    return 'MariaDB client ' + match.group(1)


def cli_check():
    output = compose('run', '--rm', '-T', 'cli', '--version')
    require(re.fullmatch(r'WP-CLI \d+\.\d+\.\d+', output), 'unexpected WP-CLI version')
    return output


def wordpress_check():
    output = compose('run', '--rm', '-T', 'cli', 'core', 'version')
    require(re.fullmatch(r'\d+\.\d+(?:\.\d+)?', output), 'unexpected WordPress version')
    return 'WordPress ' + output


def database_check():
    # Read-only queries: no initialization, resets, content edits, or user changes.
    code = '''global $wpdb;
    $u = get_user_by('login', 'developer');
    echo wp_json_encode([
      'query' => (string) $wpdb->get_var('SELECT 1'),
      'server' => $wpdb->get_var('SELECT VERSION()'),
      'local' => get_option('home') === 'http://127.0.0.1:8080'
        && get_option('siteurl') === 'http://127.0.0.1:8080'
        && wp_get_environment_type() === 'local',
      'external_http_blocked' => defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL
        && (!defined('WP_ACCESSIBLE_HOSTS') || WP_ACCESSIBLE_HOSTS === ''),
      'administrator' => $u && user_can($u, 'manage_options')
    ]);'''
    data = json.loads(compose('run', '--rm', '-T', 'cli', 'eval', code,
                              '--skip-plugins', '--skip-themes'))
    require(data.get('local') is True, 'WordPress URLs/environment are not the local sandbox')
    require(data.get('external_http_blocked') is True, 'local external HTTP isolation is missing')
    require(data.get('query') == '1', 'database SELECT 1 failed')
    require(data.get('administrator') is True, 'expected local administrator is unavailable')
    server = re.match(r'(\d+\.\d+\.\d+)-MariaDB\b', data.get('server', ''))
    require(server is not None, 'expected MariaDB server is unavailable')
    return 'SELECT 1; MariaDB server ' + server.group(1) + '; local URLs and HTTP isolation verified'


def html_ok(response):
    require(response.status == 200, 'unexpected HTTP status')
    require('text/html' in response.headers.get('Content-Type', ''), 'expected HTML response')


def homepage(client):
    # The installed theme may intentionally change the site title. Validate the
    # configured title instead of requiring the original synthetic branding.
    title = compose('run', '--rm', '-T', 'cli', 'option', 'get', 'blogname')
    require(bool(title), 'configured site title is missing')
    response = client.request('/')
    html_ok(response)
    require(title in response.body, 'expected homepage title missing')


def rest(client):
    response = client.request('/?rest_route=/wp/v2/posts')
    require(response.status == 200, 'REST endpoint returned an unexpected status')
    require('application/json' in response.headers.get('Content-Type', ''), 'expected REST JSON')
    try:
        posts = json.loads(response.body)
        valid = isinstance(posts, list) and any(
            isinstance(p, dict) and isinstance(p.get('id'), int)
            and p.get('title', {}).get('rendered') == 'Hello world!' for p in posts
        )
    except (ValueError, TypeError, AttributeError):
        valid = False
    require(valid, 'REST response does not contain the expected synthetic post')


def password():
    path = RUNTIME / '.env'
    require(path.stat().st_mode & 0o077 == 0, 'local secret file permissions are too broad')
    values = dict(line.split('=', 1) for line in path.read_text().splitlines()
                  if line and not line.startswith('#') and '=' in line)
    value = values.get('WP_ADMIN_PASSWORD')
    require(bool(value), 'local administrator credential is unavailable')
    return value


def login(client, credential, expected=True):
    page = client.request('/wp-login.php')
    html_ok(page)
    require('id="loginform"' in page.body, 'expected WordPress login form missing')
    payload = urllib.parse.urlencode({
        'log': 'developer', 'pwd': credential, 'wp-submit': 'Log In',
        'redirect_to': BASE + '/wp-admin/', 'testcookie': '1',
    }).encode()
    response = client.request('/wp-login.php', data=payload)
    if expected:
        require(response.status == 302, 'administrator authentication did not redirect')
        require(response.headers.get('Location') == BASE + '/wp-admin/',
                'administrator authentication returned an unexpected redirect')
        require(client.logged_in(), 'authenticated WordPress cookie was not issued')
    else:
        html_ok(response)
        require('id="login_error"' in response.body and not client.logged_in(),
                'incorrect credentials were not explicitly rejected')
        protected = client.request('/wp-admin/')
        target = urllib.parse.urlsplit(protected.headers.get('Location', ''))
        require(protected.status == 302 and target.scheme == 'http'
                and target.netloc == '127.0.0.1:8080' and target.path == '/wp-login.php',
                'anonymous dashboard access was not rejected safely')


def dashboard(client):
    response = client.request('/wp-admin/', timeout=DASHBOARD_TIMEOUT)
    html_ok(response)
    require(client.logged_in() and 'Dashboard' in response.body
            and 'wp-admin-bar-logout' in response.body and 'id="wpadminbar"' in response.body
            and 'id="loginform"' not in response.body,
            'authenticated dashboard content is missing or unexpected')


def performance(first, repeat):
    if repeat > SLOW_SECONDS:
        kind = 'persistent' if first > SLOW_SECONDS else 'intermittent'
        raise SmokeFailure(f'{kind} dashboard slowness: repeat exceeds {SLOW_SECONDS}s')
    if first > SLOW_SECONDS:
        return 'first-request delay recovered on repeat (consistent with a cold start)'
    return 'first and repeat dashboard requests within the performance budget'


class Reporter:
    def run(self, name, check):
        start = time.monotonic()
        try:
            detail = check()
        except Exception as error:
            elapsed = time.monotonic() - start
            # Never include arbitrary exception text, bodies, headers or command output.
            reason = str(error) if isinstance(error, SmokeFailure) else 'unexpected local check error'
            print(f'FAIL {name}: {elapsed:.3f}s — {reason}', flush=True)
            raise SmokeFailure('suite stopped at the failed check') from None
        elapsed = time.monotonic() - start
        print(f'PASS {name}: {elapsed:.3f}s' + (f' — {detail}' if detail else ''), flush=True)
        return elapsed


def main():
    report = Reporter()
    start = time.monotonic()
    try:
        report.run('PHP', php_check)
        report.run('MariaDB', mariadb_check)
        report.run('WP-CLI', cli_check)
        report.run('WordPress', wordpress_check)
        report.run('Database and local boundary', database_check)
        client = Client()
        report.run('Homepage', lambda: homepage(client))
        report.run('REST API', lambda: rest(client))
        credential = None

        def valid_login():
            nonlocal credential
            credential = password()
            login(client, credential)

        report.run('Administrator authentication', valid_login)
        first = report.run('Dashboard first request', lambda: dashboard(client))
        repeat = report.run('Dashboard repeat request', lambda: dashboard(client))
        report.run('Dashboard performance', lambda: performance(first, repeat))
        wrong = secrets.token_hex(32)
        while wrong == credential:
            wrong = secrets.token_hex(32)
        report.run('Incorrect credentials and anonymous access rejected',
                   lambda: login(Client(), wrong, expected=False))
    except SmokeFailure:
        print(f'FAIL suite: {time.monotonic() - start:.3f}s', flush=True)
        return 1
    print(f'PASS suite: {time.monotonic() - start:.3f}s', flush=True)
    return 0


if __name__ == '__main__':
    sys.exit(main())
