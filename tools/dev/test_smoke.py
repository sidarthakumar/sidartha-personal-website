"""Regression checks for smoke-test security boundaries and failure handling."""

import contextlib
import io
import unittest
from unittest.mock import patch
import urllib.error

import smoke


def html(body, status=200, **headers):
    return smoke.Response(status, {'Content-Type': 'text/html', **headers}, body)


class FakeClient:
    def __init__(self, responses, authenticated=False):
        self.responses = iter(responses)
        self.authenticated = authenticated

    def request(self, *args, **kwargs):
        return next(self.responses)

    def logged_in(self):
        return self.authenticated


class AuthenticationTests(unittest.TestCase):
    def test_valid_login_requires_cookie_and_local_redirect(self):
        client = FakeClient([
            html('<form id="loginform">'),
            html('', 302, Location=smoke.BASE + '/wp-admin/'),
        ], authenticated=True)
        smoke.login(client, 'synthetic-test-only')

    def test_incorrect_password_cannot_pass_positive_test(self):
        client = FakeClient([html('<form id="loginform">'), html('<div id="login_error">')])
        with self.assertRaisesRegex(smoke.SmokeFailure, 'authentication did not redirect'):
            smoke.login(client, 'synthetic-wrong-password')

    def test_login_redirect_without_cookie_fails(self):
        client = FakeClient([
            html('<form id="loginform">'), html('', 302, Location=smoke.BASE + '/wp-admin/'),
        ])
        with self.assertRaisesRegex(smoke.SmokeFailure, 'cookie was not issued'):
            smoke.login(client, 'synthetic-test-only')

    def test_external_redirect_is_rejected(self):
        client = FakeClient([
            html('<form id="loginform">'), html('', 302, Location='https://example.invalid/'),
        ], authenticated=True)
        with self.assertRaisesRegex(smoke.SmokeFailure, 'unexpected redirect'):
            smoke.login(client, 'synthetic-test-only')

    def test_wrong_credentials_require_explicit_error_and_protected_dashboard(self):
        client = FakeClient([
            html('<form id="loginform">'), html('<div id="login_error">'),
            html('', 302, Location=smoke.BASE + '/wp-login.php?redirect_to=local'),
        ])
        smoke.login(client, 'synthetic-wrong-password', expected=False)

    def test_unexpected_login_page_cannot_pass_negative_test(self):
        client = FakeClient([html('<form id="loginform">'), html('<h1>Maintenance</h1>')])
        with self.assertRaises(smoke.SmokeFailure):
            smoke.login(client, 'synthetic-wrong-password', expected=False)

    def test_guest_dashboard_access_fails_negative_test(self):
        client = FakeClient([
            html('<form id="loginform">'), html('<div id="login_error">'), html('Dashboard'),
        ])
        with self.assertRaisesRegex(smoke.SmokeFailure, 'anonymous dashboard access'):
            smoke.login(client, 'synthetic-wrong-password', expected=False)

    def test_dashboard_requires_authenticated_content(self):
        for response in (html('<form id="loginform">'), html('Dashboard', 503),
                         html('Dashboard wp-admin-bar-logout')):
            with self.subTest(response=response):
                with self.assertRaises(smoke.SmokeFailure):
                    smoke.dashboard(FakeClient([response], authenticated=True))

    def test_dashboard_requires_cookie_even_with_expected_markers(self):
        client = FakeClient([html('Dashboard wp-admin-bar-logout id="wpadminbar"')])
        with self.assertRaises(smoke.SmokeFailure):
            smoke.dashboard(client)


class BoundaryTests(unittest.TestCase):
    def test_homepage_requires_the_current_configured_site_title(self):
        with patch.object(smoke, 'compose', return_value='Sidartha Kumar'):
            smoke.homepage(FakeClient([html('<title>Sidartha Kumar</title>')]))
            with self.assertRaisesRegex(smoke.SmokeFailure, 'homepage title missing'):
                smoke.homepage(FakeClient([html('<title>Unexpected site</title>')]))

    def test_missing_external_http_isolation_fails(self):
        import json
        result = {'query': '1', 'server': '11.4.13-MariaDB', 'local': True,
                  'external_http_blocked': False, 'administrator': True}
        with patch.object(smoke, 'compose', return_value=json.dumps(result)):
            with self.assertRaisesRegex(smoke.SmokeFailure, 'HTTP isolation is missing'):
                smoke.database_check()

    def test_redirects_are_not_automatically_followed(self):
        client = smoke.Client()
        with patch.object(client.opener, 'open', side_effect=urllib.error.HTTPError(
                smoke.BASE + '/', 302, 'Found', {'Location': 'https://example.invalid/'},
                io.BytesIO(b''))):
            response = client.request('/')
        self.assertEqual(response.status, 302)
        self.assertIsNone(smoke.NoRedirect().redirect_request(
            None, None, 302, '', {}, 'https://example.invalid/'))

    def test_nonlocal_path_is_rejected_before_io(self):
        client = smoke.Client()
        with patch.object(client.opener, 'open') as opened:
            with self.assertRaises(smoke.SmokeFailure):
                client.request('//example.invalid/')
            opened.assert_not_called()

    def test_http_timeout_is_a_failure(self):
        client = smoke.Client()
        with patch.object(client.opener, 'open', side_effect=TimeoutError('secret-canary')):
            with self.assertRaisesRegex(smoke.SmokeFailure, 'deadline exceeded'):
                client.request('/wp-admin/', timeout=30)

    def test_total_deadline_interrupts_a_stalled_operation(self):
        import time
        with self.assertRaises(TimeoutError):
            with smoke.deadline(0.02):
                time.sleep(1)

    def test_unexpected_exception_text_is_not_logged(self):
        def unsafe_error():
            raise ValueError('secret-canary')
        output = io.StringIO()
        with contextlib.redirect_stdout(output):
            with self.assertRaises(smoke.SmokeFailure):
                smoke.Reporter().run('sample', unsafe_error)
        self.assertIn('FAIL sample:', output.getvalue())
        self.assertNotIn('secret-canary', output.getvalue())

    def test_malformed_rest_response_fails(self):
        response = smoke.Response(200, {'Content-Type': 'application/json'}, '{broken')
        with self.assertRaises(smoke.SmokeFailure):
            smoke.rest(FakeClient([response]))


class PerformanceTests(unittest.TestCase):
    def test_fast_requests_pass(self):
        self.assertIn('within', smoke.performance(0.2, 0.1))

    def test_slow_first_request_that_recovers_is_reported(self):
        self.assertIn('cold start', smoke.performance(10.2, 0.2))

    def test_repeated_slowness_fails(self):
        with self.assertRaisesRegex(smoke.SmokeFailure, 'persistent'):
            smoke.performance(10.2, 10.3)

    def test_slow_repeat_is_not_misclassified_as_cold_start(self):
        with self.assertRaisesRegex(smoke.SmokeFailure, 'intermittent'):
            smoke.performance(0.2, 10.3)


if __name__ == '__main__':
    unittest.main()
