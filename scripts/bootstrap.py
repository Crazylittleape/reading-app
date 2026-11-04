#!/usr/bin/env python3
"""Create local-only configuration without importing deployment credentials or books."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
from urllib.parse import urlsplit


ROOT = Path(__file__).resolve().parents[1]
SECRET_KEYS = (
    'MYSQL_PASSWORD', 'MYSQL_ROOT_PASSWORD', 'APP_API_KEY', 'APP_ENCRYPTION_KEY',
    'APP_BOOK_KEY', 'ADMIN_PASSWORD', 'ADMIN_CODE',
)


def read_env(path):
    values = {}
    if not path.exists():
        return values
    for line in path.read_text(encoding='utf-8').splitlines():
        line = line.strip()
        if not line or line.startswith('#'):
            continue
        name, separator, value = line.partition('=')
        if not separator or not re.fullmatch(r'[A-Z][A-Z0-9_]*', name):
            raise ValueError('Invalid local environment file')
        values[name] = value.strip().strip('"').strip("'")
    return values


def validate(values):
    url = urlsplit(values['APP_URL'])
    if (url.scheme not in ('http', 'https') or not url.hostname or url.username
            or url.password or url.query or url.fragment or url.path not in ('', '/')):
        raise ValueError('APP_URL must be an HTTP or HTTPS origin without a path')
    if re.search(r'[\s"\'<>]', values['APP_URL']):
        raise ValueError('APP_URL contains unsupported characters')
    if not values['APP_NAME'] or re.search(r'["\'<>$\r\n]', values['APP_NAME']):
        raise ValueError('APP_NAME must be a nonempty literal display name')
    if not re.fullmatch(r'[A-Za-z0-9_-]{1,48}', values['ADMIN_USERNAME']):
        raise ValueError('ADMIN_USERNAME must use letters, digits, underscores or hyphens')
    if not values['HTTP_PORT'].isdigit() or not 1024 <= int(values['HTTP_PORT']) <= 65535:
        raise ValueError('HTTP_PORT must be between 1024 and 65535')
    if not re.fullmatch(r'[A-Za-z0-9.:_-]+', values['BIND_ADDRESS']):
        raise ValueError('BIND_ADDRESS contains unsupported characters')
    for key in SECRET_KEYS:
        if not re.fullmatch(r'[A-Za-z0-9_-]{16,}', values.get(key, '')):
            raise ValueError(key + ' must have at least 16 URL-safe characters')
    return values


def write_private(path, content):
    path.parent.mkdir(parents=True, exist_ok=True)
    descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(descriptor, 'w', encoding='utf-8') as stream:
        stream.write(content)
    path.chmod(0o600)


def sql_string(value):
    return "CONVERT(X'" + value.encode('utf-8').hex() + "' USING utf8mb4)"


def bootstrap(root, app_url=None, app_name=None):
    env_file = root / '.env'
    values = {
        'APP_URL': 'http://localhost:18087', 'APP_NAME': 'Reading Library',
        'BIND_ADDRESS': '127.0.0.1', 'HTTP_PORT': '18087', 'ADMIN_USERNAME': 'readeradmin',
    }
    existing = read_env(env_file)
    values.update(existing)
    for key in SECRET_KEYS:
        if not values.get(key) or values[key] == 'CHANGE_ME':
            values[key] = secrets.token_hex(24)
    if app_url:
        values['APP_URL'] = app_url.rstrip('/')
    if app_name:
        values['APP_NAME'] = app_name
    validate(values)
    write_private(env_file, '\n'.join(key + '=' + value for key, value in values.items()) + '\n')
    for folder in ('site/caches', 'site/attachment', 'site/app', 'db-init', 'uniapp/public'):
        (root / folder).mkdir(parents=True, exist_ok=True)

    # The browser must know this signing identifier; it is not an authorization secret.
    public_config = {
        'apiBase': '', 'apiKey': values['APP_API_KEY'],
        'serverOrigin': values['APP_URL'], 'appName': values['APP_NAME'],
    }
    js = 'window.READING_APP_CONFIG = ' + json.dumps(public_config, ensure_ascii=True) + ';\n'
    write_private(root / 'uniapp/public/runtime-config.js', js)
    write_private(root / 'site/app/runtime-config.js', js)
    # These files contain public browser configuration and must be readable by Apache.
    (root / 'uniapp/public/runtime-config.js').chmod(0o644)
    (root / 'site/app/runtime-config.js').chmod(0o644)
    native_config = dict(public_config, apiBase=values['APP_URL'])
    write_private(root / 'uniapp/runtime-config.json', json.dumps(native_config, indent=2) + '\n')

    # This file is ignored and used only by MySQL's empty-volume initialization.
    row = [values['ADMIN_USERNAME'], hashlib.md5(values['ADMIN_PASSWORD'].encode()).hexdigest(), 'Library administrator']
    sql = ('SET NAMES utf8mb4;\nINSERT INTO mc_admin (name,pass,nichen,sid) VALUES ('
           + ','.join(sql_string(value) for value in row) + ',0);\n')
    write_private(root / 'db-init/90-local-bootstrap.sql', sql)
    # The MySQL entrypoint switches to its own UID before consuming this ignored file.
    (root / 'db-init/90-local-bootstrap.sql').chmod(0o644)
    return values


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app-url')
    parser.add_argument('--app-name')
    args = parser.parse_args()
    try:
        bootstrap(ROOT, args.app_url, args.app_name)
    except (ValueError, OSError) as error:
        parser.exit(1, 'Bootstrap failed: ' + str(error) + '\n')
    print('Local configuration ready; credentials are stored in ignored .env, not printed.')
    print('Existing credentials are preserved. Changing .env does not reset an existing database.')


if __name__ == '__main__':
    main()
