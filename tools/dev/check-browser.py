#!/usr/bin/env python3
"""Local-only Chromium layout, interaction, accessibility, and public-page checks."""
import argparse
import json
from pathlib import Path
import time
from urllib.parse import urlsplit
from playwright.sync_api import sync_playwright

BASE = 'http://127.0.0.1:8080'
parser = argparse.ArgumentParser()
parser.add_argument('--axe', type=Path, required=True, help='Local axe-core 4.10.3 axe.min.js; never loaded by the website')
parser.add_argument('--output', type=Path, default=Path('/tmp/skp-browser-report'))
args = parser.parse_args()
args.output.mkdir(parents=True, exist_ok=True)
def require(condition, message):
    if not condition:
        raise AssertionError(message)

checks = []
hero_metrics = []

with sync_playwright() as automation:
    browser = automation.chromium.launch(executable_path='/usr/bin/chromium', headless=True, args=['--no-sandbox'])
    context = browser.new_context(viewport={'width': 1440, 'height': 1000}, device_scale_factor=1)
    blocked = []

    def local_only(route):
        url = route.request.url
        if urlsplit(url).netloc == '127.0.0.1:8080' and urlsplit(url).scheme == 'http':
            route.continue_()
        else:
            blocked.append(urlsplit(url).hostname or 'non-http')
            route.abort()

    context.route('**/*', local_only)
    page = context.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(type(error).__name__))
    pages = ['/', '/about/', '/skills/', '/projects/', '/journey/', '/insights/', '/contact/']
    for width, height in ((1440, 1000), (1024, 900), (768, 1024), (430, 932), (390, 844), (360, 800), (320, 740)):
        page.set_viewport_size({'width': width, 'height': height})
        for path in pages:
            start = time.monotonic()
            response = page.goto(BASE + path, wait_until='networkidle', timeout=30000)
            require(response.status == 200, f'{path}: unexpected status')
            require(page.locator('main h1').count() == 1, f'{path}: expected one H1')
            require(not page.evaluate('document.documentElement.scrollWidth > innerWidth'), f'{path}: horizontal overflow at {width}px')
            levels = page.locator('main h1, main h2, main h3, main h4, main h5, main h6').evaluate_all('(nodes) => nodes.map(node => Number(node.tagName[1]))')
            require(all(b <= a + 1 for a, b in zip(levels, levels[1:])), f'{path}: skipped heading level')
            require('developer@example.test' not in page.content(), f'{path}: synthetic administrator email exposed')
            require('Lorem ipsum' not in page.locator('main').inner_text(), f'{path}: unfinished filler copy')
            page.add_script_tag(path=str(args.axe))
            result = page.evaluate('''async () => {
              const result = await axe.run(document, {runOnly: {type: 'tag', values: ['wcag2a','wcag2aa','wcag21a','wcag21aa','best-practice']}});
              return result.violations.map(v => ({id:v.id, impact:v.impact, nodes:v.nodes.map(n=>n.target)}));
            }''')
            if result:
                (args.output / 'accessibility-findings.json').write_text(json.dumps(result, indent=2))
            require(not result, f'{path}: accessibility findings at {width}px: {[item["id"] for item in result]}')
            checks.append({'path': path, 'width': width, 'status': 'PASS', 'seconds': round(time.monotonic() - start, 3)})
            if path == '/':
                require(page.locator('.hero h1').inner_text() == 'Building practical technology.\nOne project at a time.', 'approved default headline missing')
                require(page.locator('#learning-focus .skill-card').count() == 6, 'homepage must present all six published learning groups')
                require(page.locator('.hero-actions a').all_text_contents()[0].strip() == 'View projects', 'primary action wording incorrect')
                for name, path_target in (('View projects', '/projects/'), ('About my journey', '/about/')):
                    link = page.get_by_role('link', name=name, exact=True)
                    require(link.get_attribute('href') == BASE + path_target, 'hero action target incorrect')
                    require(link.bounding_box()['height'] >= 44, 'hero action touch target too small')
                metrics = page.evaluate('''() => ({width:innerWidth,
                    card_height:Math.round(document.querySelector('.identity-card').getBoundingClientRect().height),
                    next_section_top:Math.round(document.querySelector('#learning-focus').getBoundingClientRect().top+scrollY),
                    intro_font:parseFloat(getComputedStyle(document.querySelector('.hero-intro')).fontSize),
                    smallest_label:Math.min(...[...document.querySelectorAll('.hero .eyebrow, .identity-card .small-label, .edition, .identity-status, .skill-card .small-label, .skill-card .tags span')].map(n=>parseFloat(getComputedStyle(n).fontSize)))})''')
                require(metrics['intro_font'] >= 16 and metrics['smallest_label'] >= 12, 'homepage typography is too small')
                if width <= 430:
                    require(metrics['card_height'] <= 240, 'phone identity card is too tall')
                    require(metrics['next_section_top'] <= 1020, 'learning section is excessively far below the hero')
                hero_metrics.append(metrics)
                page.screenshot(path=str(args.output / f'home-{width}.png'), full_page=True)
                page.screenshot(path=str(args.output / f'home-{width}-viewport.png'))
                if width <= 768:
                    toggle = page.get_by_role('button', name='Menu')
                    toggle.focus(); page.keyboard.press('Enter')
                    require(toggle.get_attribute('aria-expanded') == 'true', 'mobile menu failed to open')
                    page.keyboard.press('Tab')
                    require(page.evaluate('document.activeElement.closest("#primary-navigation") !== null'), 'mobile links unreachable by keyboard')
                    require(page.evaluate('getComputedStyle(document.activeElement).outlineStyle !== "none"'), 'keyboard focus indicator missing')
                    page.keyboard.press('Escape')
                    require(toggle.get_attribute('aria-expanded') == 'false', 'mobile menu failed to close')
                if width == 1440:
                    page.screenshot(path=str(args.output / 'home-desktop-viewport.png'))
            if width == 1440 and path in ('/projects/', '/about/', '/journey/'):
                page.screenshot(path=str(args.output / (path.strip('/') + '-desktop.png')), full_page=True)
            print(f'PASS layout + axe: {path} at {width}px', flush=True)

    page.goto(BASE + '/projects/?status=planned', wait_until='networkidle')
    require(page.locator('.empty-state').is_visible(), 'planned filter: expected honest empty state')
    page.goto(BASE + '/projects/?status=in-progress', wait_until='networkidle')
    require(page.locator('.project-card').count() == 1, 'in-progress project filter failed')
    page.goto(BASE + '/projects/?project_q=personal', wait_until='networkidle')
    require(page.locator('.project-card').count() == 1, 'project search failed')
    page.goto(BASE + '/projects/?status[]=completed&technology[]=1&project_q[]=test', wait_until='networkidle')
    require(page.locator('.project-card').count() == 1, 'malformed filter inputs are not handled safely')
    page.locator('#project-q').fill('\"><img src=x onerror=window.__qaXss=1>')
    page.get_by_role('button', name='Filter').click()
    page.wait_for_load_state('networkidle')
    require(page.evaluate('window.__qaXss === undefined'), 'reflected script executed')
    print('PASS project search, filters, empty state, and malicious input checks', flush=True)

    page.goto(BASE + '/', wait_until='networkidle')
    page.keyboard.press('Tab')
    require(page.evaluate('document.activeElement.classList.contains("skip-link")'), 'skip link is not first in keyboard order')
    page.keyboard.press('Enter')
    require(page.evaluate('document.activeElement.id === "main-content"'), 'skip link did not move focus to main')
    toggle = page.get_by_role('button', name='Menu')
    toggle.focus(); page.keyboard.press('Enter')
    require(toggle.get_attribute('aria-expanded') == 'true', 'keyboard menu did not open')
    page.keyboard.press('Tab')
    require(page.evaluate('document.activeElement.closest("#primary-navigation") !== null'), 'keyboard cannot reach mobile links')
    page.keyboard.press('Escape')
    require(toggle.get_attribute('aria-expanded') == 'false' and page.evaluate('document.activeElement.classList.contains("menu-toggle")'), 'Escape did not close menu and restore focus')
    page.emulate_media(reduced_motion='reduce')
    require(page.evaluate('getComputedStyle(document.documentElement).scrollBehavior') == 'auto', 'reduced-motion scrolling is not respected')
    print('PASS keyboard navigation, skip link, menu focus, and reduced motion', flush=True)

    page.goto(BASE + '/insights/', wait_until='networkidle')
    article_url = page.locator('.article-card .card-title a').first.get_attribute('href')
    require(urlsplit(article_url).netloc == '127.0.0.1:8080', 'unexpected article origin')
    require(page.goto(article_url, wait_until='networkidle').status == 200, 'single article failed')
    require(page.locator('main h1').count() == 1 and page.locator('.prose').first.inner_text(), 'single article content missing')
    page.goto(BASE + '/projects/', wait_until='networkidle')
    project_url = page.locator('.project-card .card-title a').first.get_attribute('href')
    require(page.goto(project_url, wait_until='networkidle').status == 200, 'single project failed')
    require('In Progress' in page.locator('.status').text_content(), 'single project status missing')
    require(page.goto(BASE + '/privacy/', wait_until='networkidle').status == 404, 'privacy draft is publicly accessible')
    require(page.goto(BASE + '/this-page-does-not-exist/', wait_until='networkidle').status == 404, '404 page status incorrect')
    print('PASS article/project detail pages, private draft, and real 404', flush=True)
    require(not errors, f'JavaScript errors detected: {len(errors)}')
    require(not blocked, 'website attempted external browser requests (hosts recorded only in memory)')
    context.close()

    no_js = browser.new_context(java_script_enabled=False, viewport={'width': 390, 'height': 844})
    no_js.route('**/*', local_only)
    fallback = no_js.new_page()
    fallback.goto(BASE + '/', wait_until='networkidle')
    require(fallback.locator('#primary-navigation').is_visible(), 'navigation unavailable without JavaScript')
    require(fallback.get_by_role('link', name='Projects', exact=True).is_visible(), 'no-JavaScript navigation link missing')
    no_js.close()
    browser.close()

(args.output / 'results.json').write_text(json.dumps({'pages': checks, 'hero_metrics': hero_metrics, 'javascript_errors': len(errors), 'external_requests': len(blocked), 'interactions': 'PASS', 'no_javascript_navigation': 'PASS'}, indent=2))
print(f'PASS: {len(checks)} responsive page/axe checks plus interaction/security checks; screenshots and results saved locally')
