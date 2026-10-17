"""Read-only checks for the owner's public site. Never authenticate or mutate."""
import json
import socket
import ssl
import sys
import unittest
from html.parser import HTMLParser
from urllib.error import HTTPError
from urllib.parse import urljoin, urlsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener

PRODUCTION = 'https://sidarthakumar.com'
STAGING = 'https://staging.sidarthakumar.com'
LIMIT = 2 * 1024 * 1024


class SameOriginRedirects(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        old, new = urlsplit(req.full_url), urlsplit(newurl)
        if new.scheme != 'https' or new.netloc != old.netloc:
            raise ValueError('Redirect leaves the exact approved HTTPS origin')
        return super().redirect_request(req, fp, code, msg, headers, newurl)


class Metadata(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonicals = []
        self.robots = []
        self.title = ''
        self.in_title = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'title':
            self.in_title = True
        if tag == 'link' and 'canonical' in a.get('rel', '').lower().split():
            self.canonicals.append(a.get('href', ''))
        if tag == 'meta' and a.get('name', '').lower() in ('robots', 'googlebot'):
            self.robots.append(a.get('content', '').lower())

    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title += data


def fetch(url):
    request = Request(url, headers={'User-Agent': 'SidarthaOwnerHealthCheck/1.0'})
    opener = build_opener(SameOriginRedirects())
    try:
        response = opener.open(request, timeout=20)
    except HTTPError as error:
        response = error
    with response:
        body = response.read(LIMIT + 1)
        if len(body) > LIMIT:
            raise ValueError('Response exceeds health-check size limit')
        return response.status, response.headers, body.decode('utf-8', errors='replace')


def homepage_errors(status, headers, body):
    errors = []
    if status != 200:
        return ['Homepage HTTP status is not 200']
    metadata = Metadata()
    metadata.feed(body)
    if not metadata.title.strip():
        errors.append('Homepage title is missing')
    if len(metadata.canonicals) != 1:
        errors.append('Homepage must have exactly one canonical URL')
    elif urljoin(PRODUCTION + '/', metadata.canonicals[0]).rstrip('/') != PRODUCTION:
        errors.append('Homepage canonical does not match production')
    robot_values = metadata.robots + [headers.get('X-Robots-Tag', '').lower()]
    if any('noindex' in value or 'none' in value.replace(',', ' ').split() for value in robot_values):
        errors.append('Production homepage has a noindex directive')
    return errors


def run():
    results = []
    checks = [
        ('production_homepage', PRODUCTION + '/'),
        ('production_sitemap', PRODUCTION + '/wp-sitemap.xml'),
        ('staging_access_control', STAGING + '/'),
    ]
    for name, url in checks:
        try:
            status, headers, body = fetch(url)
            if name == 'production_homepage':
                errors = homepage_errors(status, headers, body)
            elif name == 'production_sitemap':
                import xml.etree.ElementTree as ET
                errors = []
                if status != 200:
                    errors.append('WordPress sitemap is unavailable')
                else:
                    try:
                        root = ET.fromstring(body)
                        if root.tag.split('}')[-1] not in ('sitemapindex', 'urlset'):
                            errors.append('Sitemap XML has an unexpected root')
                    except ET.ParseError:
                        errors.append('Sitemap is not valid XML')
            else:
                errors = [] if status == 401 and headers.get('WWW-Authenticate') else [
                    'Staging did not require HTTP authentication; review access control']
            results.append({'check': name, 'passed': not errors, 'errors': errors})
        except (ValueError, OSError, ssl.SSLError, socket.timeout) as error:
            # Never print response bodies, headers, cookies or exception URLs.
            results.append({'check': name, 'passed': False, 'errors': [type(error).__name__]})
    print(json.dumps({'checks': results}, indent=2))
    return 0 if all(result['passed'] for result in results) else 1


class HealthTests(unittest.TestCase):
    def test_good_homepage(self):
        self.assertEqual(homepage_errors(200, {}, '<title>Sidartha</title><link rel="canonical" href="https://sidarthakumar.com/">'), [])

    def test_wrong_canonical(self):
        self.assertTrue(homepage_errors(200, {}, '<title>X</title><link rel="canonical" href="https://staging.sidarthakumar.com/">'))

    def test_noindex_header(self):
        self.assertTrue(homepage_errors(200, {'X-Robots-Tag': 'noindex'}, '<title>X</title><link rel="canonical" href="/">'))

    def test_noindex_meta(self):
        self.assertTrue(homepage_errors(200, {}, '<title>X</title><link rel="canonical" href="/"><meta name="robots" content="noindex,follow">'))

    def test_duplicate_canonical(self):
        self.assertTrue(homepage_errors(200, {}, '<title>X</title><link rel="canonical" href="/"><link rel="canonical" href="/">'))

    def test_redirect_escape_rejected(self):
        handler = SameOriginRedirects()
        for target in ('http://sidarthakumar.com/', 'https://other.example/'):
            with self.assertRaises(ValueError):
                handler.redirect_request(Request(PRODUCTION), None, 302, '', {}, target)


if __name__ == '__main__':
    if '--self-test' in sys.argv:
        unittest.main(argv=[sys.argv[0]])
    else:
        sys.exit(run())
