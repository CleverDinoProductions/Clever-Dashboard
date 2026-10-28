"""Exercise sign-in over HTTP with isolated account and session storage.

Run with Python 3 and PHP 8 (PDO SQLite): python3 tests/login-session-test.py
"""
import http.cookiejar
import os
from pathlib import Path
import re
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def client():
    return urllib.request.build_opener(
        urllib.request.ProxyHandler({}), NoRedirect(),
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(browser, path, fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    try:
        return browser.open(base + path, data, timeout=5)
    except urllib.error.HTTPError as response:
        return response


def token(response):
    assert 'no-store' in response.headers.get('Cache-Control', '')
    match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', response.read().decode())
    assert match, 'A recoverable form must contain a session token.'
    return match[1]


with tempfile.TemporaryDirectory(prefix='clever-login-') as directory:
    env = dict(os.environ, CLEVER_ACCOUNTS_DB=str(Path(directory) / 'accounts.sqlite3'))
    subprocess.run(['php', '-r',
                    'require "lib/auth.php"; clever_create_user("session_test", '
                    '"session@example.test", "regression-password");'],
                   cwd=ROOT, env=env, check=True)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}'
    with tempfile.TemporaryFile() as log:
        server = subprocess.Popen(
            ['php', '-d', 'session.cache_limiter=', '-d', f'session.save_path={directory}',
             '-S', f'127.0.0.1:{port}', '-t', str(ROOT)], env=env, stdout=log, stderr=log)
        try:
            for _ in range(100):
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=.1):
                        break
                except OSError:
                    if server.poll() is not None:
                        raise RuntimeError('PHP server exited before startup.')
                    time.sleep(.05)
            else:
                raise RuntimeError('PHP server did not start.')

            for destination in [None, '/account/', '/football-stats/', '/youtube-dashboard/', '/']:
                path = '/account/login.php'
                if destination:
                    path += '?' + urllib.parse.urlencode({'return': destination})
                browser = client()
                csrf = token(request(browser, path))
                response = request(browser, path, {
                    'csrf_token': csrf, 'identity': 'session_test',
                    'password': 'regression-password', 'return': destination or '/account/'})
                assert response.code == 302
                assert response.headers['Location'] == (destination or '/account/')
                response = request(browser, '/account/login.php')
                assert response.code == 302, 'Successful sign-in must persist across routes.'

            for invalid in ['', 'stale-token', None]:
                browser = client()
                token(request(browser, '/account/login.php'))
                fields = {'identity': 'session_test', 'password': 'regression-password',
                          'return': '/youtube-dashboard/'}
                if invalid is not None:
                    fields['csrf_token'] = invalid
                response = request(browser, '/account/login.php', fields)
                assert response.code == 419, 'Invalid tokens must reject valid credentials.'
                csrf = token(response)
                fields['csrf_token'] = csrf
                response = request(browser, '/account/login.php', fields)
                assert response.code == 302
                assert response.headers['Location'] == '/youtube-dashboard/'

            # An expired session must recover without accepting the original POST.
            browser = client()
            csrf = token(request(browser, '/account/login.php'))
            for session_file in Path(directory).glob('sess_*'):
                session_file.unlink()
            response = request(browser, '/account/login.php', {
                'csrf_token': csrf, 'identity': 'session_test', 'password': 'regression-password'})
            assert response.code == 419
            assert token(response) != csrf

            # Missing session and missing submitted token must never compare equal.
            response = request(client(), '/account/login.php', {
                'identity': 'session_test', 'password': 'regression-password'})
            assert response.code == 419
            token(response)
            print('Login session HTTP regression tests passed.')
        finally:
            server.terminate()
            server.wait(timeout=5)
