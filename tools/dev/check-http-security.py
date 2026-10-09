#!/usr/bin/env python3
"""Exercise actual WordPress HTTP authentication/CSRF and native profile controls."""
import urllib.parse
from playwright.sync_api import sync_playwright
from smoke import BASE, Client, database_check, login, password, require

database_check()
anonymous = Client()
payload = urllib.parse.urlencode({'title': 'Unauthorized QA request', 'status': 'publish'}).encode()
response = anonymous.request('/wp-json/wp/v2/sk_project', data=payload)
require(response.status in (401, 403), 'anonymous HTTP REST creation was not rejected')
print('PASS anonymous HTTP REST creation rejected')
client = Client()
login(client, password())
response = client.request('/wp-json/wp/v2/sk_project', data=payload)
require(response.status in (401, 403), 'cookie-only REST request without nonce was not rejected')
print('PASS cookie-authenticated REST write without nonce rejected')
response = client.request('/wp-json/wp/v2/sk_project', data=payload + b'&_wpnonce=invalid')
require(response.status == 403, 'invalid REST nonce was not rejected')
print('PASS invalid REST nonce rejected')
response = client.request('/wp-admin/admin-post.php', data=b'action=skp_starter&_wpnonce=invalid')
require(response.status == 403, 'invalid starter action nonce was not rejected')
print('PASS invalid starter-import action nonce rejected')

with sync_playwright() as automation:
    browser = automation.chromium.launch(executable_path='/usr/bin/chromium', headless=True, args=['--no-sandbox'])
    context = browser.new_context(viewport={'width': 1280, 'height': 900})
    context.route('**/*', lambda route: route.continue_() if route.request.url.startswith(BASE + '/') else route.abort())
    for cookie in client.cookies:
        context.add_cookies([{'name': cookie.name, 'value': cookie.value, 'domain': '127.0.0.1', 'path': cookie.path, 'secure': cookie.secure, 'httpOnly': True}])
    page = context.new_page()
    response = page.goto(BASE + '/wp-admin/options-general.php?page=skp-profile', wait_until='networkidle')
    require(response.status == 200 and page.locator('#skp-name').input_value() == 'Sidartha Kumar', 'native profile settings UI did not load')
    require(page.locator('#skp-public_email').input_value() == '', 'unexpected initial public email')
    for field in ('headline', 'introduction', 'focus_label', 'focus_heading', 'focus_introduction'):
        require(page.locator('#skp-' + field).is_editable(), 'native homepage setting is not editable')
    print('PASS native headline, introduction, and learning section controls remain editable')
    page.get_by_role('button', name='Choose from media library').click()
    page.locator('.media-modal').wait_for(state='visible')
    page.locator('.media-modal-close').click()
    require(page.locator('#skp-photo').input_value() == '0', 'media inspection unexpectedly changed the profile')
    print('PASS native profile editor and WordPress media chooser; no settings modified')
    context.close(); browser.close()
print('PASS HTTP security and native editor controls')
