"""Check the actual static output, including links, metadata, and the public-file boundary."""
import gzip
import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT = Path('site-dist')
ORIGIN = 'https://sidarthakumar.com'
failures = []

class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.titles = 0
        self.h1s = 0
        self.description = []
        self.canonical = []
        self.links = []
        self.ids = set()
        self.schemas = []
        self.schema = None
        self.noindex = False
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'title': self.titles += 1
        if tag == 'h1': self.h1s += 1
        if attrs.get('id'): self.ids.add(attrs['id'])
        if tag == 'meta' and attrs.get('name') == 'description': self.description.append(attrs.get('content', ''))
        if tag == 'meta' and attrs.get('name') == 'robots': self.noindex = 'noindex' in attrs.get('content', '')
        if tag == 'link' and attrs.get('rel') == 'canonical': self.canonical.append(attrs.get('href', ''))
        for key in ['src', 'href']:
            if attrs.get(key): self.links.append((tag, attrs[key]))
        if tag == 'script':
            if attrs.get('type') == 'application/ld+json': self.schema = ''
            elif not attrs.get('src'): failures.append('Unexpected inline executable script')
    def handle_data(self, data):
        if self.schema is not None: self.schema += data
    def handle_endtag(self, tag):
        if tag == 'script' and self.schema is not None:
            try: self.schemas.append(json.loads(self.schema))
            except ValueError: failures.append('Invalid structured data')
            self.schema = None

files = [p for p in ROOT.rglob('*') if p.is_file()]
allowed = {'.html', '.css', '.js', '.svg', '.xml', '.txt', '.png', '.jpg', '.webp', '.woff2'}
for file in files:
    if file.name == '.htaccess' and file.parent == ROOT: continue
    if file.suffix not in allowed or any(part.startswith('.') for part in file.relative_to(ROOT).parts): failures.append(f'Unexpected public file: {file.relative_to(ROOT)}')

pages = {}
for file in ROOT.rglob('*.html'):
    page = Page()
    page.feed(file.read_text())
    route = '/' + str(file.relative_to(ROOT)).replace('index.html', '')
    pages[route] = page
    if page.titles != 1 or page.h1s != 1: failures.append(f'{route}: expected one title and one H1')
    if len(page.description) != 1 or not 30 <= len(page.description[0]) <= 240: failures.append(f'{route}: missing or invalid description')
    if page.canonical != [ORIGIN + route]: failures.append(f'{route}: invalid canonical')
    if route == '/404.html':
        if not page.noindex: failures.append('404 must be noindex')
    elif page.noindex: failures.append(f'{route}: unexpected noindex')
    if route != '/404.html' and not page.schemas: failures.append(f'{route}: missing structured data')
    if len(gzip.compress(file.read_bytes())) > 35000: failures.append(f'{route}: HTML exceeds the 35 KB compressed page budget')

for route, page in pages.items():
    for tag, raw in page.links:
        link = urlsplit(raw)
        if link.scheme in ('mailto', 'tel'): continue
        if link.scheme and link.scheme != 'https': failures.append(f'{route}: unsafe link scheme')
        if link.netloc and link.netloc != 'sidarthakumar.com':
            if tag in ('script', 'link', 'img', 'iframe', 'source', 'video', 'audio'): failures.append(f'{route}: external runtime dependency')
            continue
        target = unquote(link.path) or route
        if not target.startswith('/'): failures.append(f'{route}: relative link needs review'); continue
        path = ROOT / target.lstrip('/')
        if path.is_dir(): path /= 'index.html'
        if not path.is_file(): failures.append(f'{route}: missing internal target {target}')
        if link.fragment and target in pages and link.fragment not in pages[target].ids and link.fragment != 'top': failures.append(f'{route}: missing anchor target')

required = ['index.html', 'about/index.html', 'skills/index.html', 'projects/index.html', 'journey/index.html', 'insights/index.html', 'contact/index.html', 'privacy-policy/index.html', '404.html', 'sitemap.xml', 'feed.xml', '.htaccess', 'robots.txt']
for name in required:
    if not (ROOT / name).is_file(): failures.append(f'Missing output: {name}')
css_bytes = sum(len(gzip.compress(p.read_bytes())) for p in files if p.suffix == '.css')
js_bytes = sum(len(gzip.compress(p.read_bytes())) for p in files if p.suffix == '.js')
if css_bytes > 18000 or js_bytes > 12000: failures.append('CSS or JS exceeds the compressed size budget')
if failures:
    for failure in sorted(set(failures)): print(f'FAIL: {failure}')
    raise SystemExit(1)
print(f'Output verified: {len(pages)} pages; CSS {css_bytes:,} B and JavaScript {js_bytes:,} B compressed. Internal links and metadata pass.')
