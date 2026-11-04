#!/usr/bin/env python3
"""Reject credentials, runtime books and database exports from tracked or staged files."""
import argparse
from pathlib import Path, PurePosixPath
import re
import subprocess


ROOT = Path(__file__).resolve().parents[1]
SCHEMA = 'db-init/01-schema.sql'
BAD_EXTENSIONS = {'.pem', '.key', '.p12', '.pfx', '.epub', '.pdf', '.mobi', '.azw', '.azw3',
                  '.sqlite', '.sqlite3', '.db', '.dump', '.apk', '.ipa', '.wgt', '.zip', '.tgz'}
SECRET_DEFINES = ('Mc_Sqlpwd', 'Mc_Encryption_Key', 'Mc_Book_Key', 'Admin_Code', 'Web_Rkpass')


def inspect_file(name, content):
    problems = []
    path = PurePosixPath(name)
    if (path.name in {'secrets.json', 'deployment.json', 'runtime-config.json', 'runtime-config.js'}
            or (path.name.startswith('.env') and not path.name.endswith('.example'))):
        problems.append('local credential or runtime configuration file')
    if path.suffix.lower() in BAD_EXTENSIONS:
        problems.append('private data, database or packaged artifact')
    if path.name.lower().endswith(('.tar.gz', '.sql.gz', '.sql.bz2', '.sql.xz')):
        problems.append('archive or database export')
    if any(part in {'node_modules', 'dist', 'unpackage', 'backups', '__pycache__'} for part in path.parts):
        problems.append('generated dependency, build or runtime directory')
    if (name.startswith(('site/caches/', 'site/attachment/', 'site/app/')) and path.name != '.gitkeep'):
        problems.append('runtime cache, uploaded content or built website')
    if path.suffix == '.sql' and name != SCHEMA:
        problems.append('only the reviewed schema-only SQL file is allowed')
    try:
        text = content.decode('utf-8')
    except UnicodeDecodeError:
        return problems
    if name == SCHEMA and re.search(r'^\s*(INSERT|UPDATE|REPLACE|LOAD\s+DATA)\b', text, re.I | re.M):
        problems.append('schema contains row data')
    if re.search(r'-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----\s+[A-Za-z0-9+/=]{40}', text):
        problems.append('embedded private key')
    if re.search(r'\b(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{40,}|AKIA[A-Z0-9]{16})\b', text):
        problems.append('credential token')
    if path.suffix.lower() == '.php':
        for key in SECRET_DEFINES:
            if re.search(r"define\(\s*['\"]" + key + r"['\"]\s*,\s*['\"][^'\"]+['\"]", text):
                problems.append('hardcoded PHP deployment credential')
                break
    if re.search(r"(?:export\s+)?const\s+API_KEY\s*=\s*['\"][^'\"]+['\"]", text):
        problems.append('hardcoded frontend signing credential')
    if re.search(r'\.tail[a-z0-9]+\.ts\.net\b', text, re.I):
        problems.append('private deployment hostname')
    return problems


def git(*args):
    return subprocess.check_output(['git', '-C', str(ROOT), *args])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--staged', action='store_true', help='Read exact staged blobs, not worktree files')
    args = parser.parse_args()
    command = ['diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z'] if args.staged else ['ls-files', '-z']
    names = [name.decode() for name in git(*command).split(b'\0') if name]
    issues = []
    for name in names:
        content = git('show', ':' + name) if args.staged else (ROOT / name).read_bytes()
        for problem in inspect_file(name, content):
            issues.append(name + ': ' + problem)
    if issues:
        print('\n'.join(issues))
        raise SystemExit(1)
    print('Repository hygiene passed for ' + str(len(names)) + (' staged' if args.staged else ' tracked') + ' files.')


if __name__ == '__main__':
    main()
