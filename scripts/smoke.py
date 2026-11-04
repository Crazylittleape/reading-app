#!/usr/bin/env python3
"""Check a fresh local stack without printing credentials or importing private books."""
import argparse
import hashlib
import http.cookiejar
import json
import time
from urllib.error import HTTPError
from urllib.parse import urlencode
from urllib.request import HTTPCookieProcessor, ProxyHandler, Request, build_opener

from bootstrap import ROOT, read_env


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--base-url', default='http://localhost:18087')
    args = parser.parse_args()
    base = args.base_url.rstrip('/')
    env = read_env(ROOT / '.env')
    if 'APP_API_KEY' not in env:
        parser.exit(1, 'Run bootstrap.py before smoke testing.\n')
    client = build_opener(ProxyHandler({}), HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(path, values=None):
        req = Request(base + path, data=urlencode(values).encode() if values is not None else None,
                      headers={'Accept': 'application/json'})
        try:
            with client.open(req, timeout=15) as response:
                return response.status, response.read()
        except HTTPError as error:
            return error.code, error.read()

    assert request('/healthz.json')[0] == 200
    assert request('/app/')[0] == 200
    assert request('/app/runtime-config.js')[0] == 200
    params = {'deviceid': 'local-smoke-check', 'facility': 'android',
              'timestamp': str(int(time.time() * 1000)), 'user_id': '0'}
    raw = '&'.join(key + '=' + params[key] for key in sorted(params))
    params['sign'] = hashlib.md5((raw + env['APP_API_KEY']).encode()).hexdigest().upper()
    status, content = request('/index.php/appv1/book/data?' + urlencode(params))
    body = json.loads(content)
    assert status == 200 and body['code'] == 1 and isinstance(body['list'], list)
    assert request('/manage.php/login')[0] == 200
    status, content = request('/manage.php/login/save', {
        'name': env['ADMIN_USERNAME'], 'pass': env['ADMIN_PASSWORD'], 'code': env['ADMIN_CODE'],
    })
    assert status == 200 and json.loads(content)['code'] == 1
    status, content = request('/manage.php/book/ajax?page=1&limit=10')
    assert status == 200 and json.loads(content)['code'] == 0
    for path in ['/sys/libs/db.php', '/caches/txt/', '/index.php/appv1/APPv1', '/attachment/index.php']:
        assert request(path)[0] in (403, 404), path
    print('Smoke passed: health, H5, signed book API, admin login, book management and blocked private paths.')


if __name__ == '__main__':
    main()
