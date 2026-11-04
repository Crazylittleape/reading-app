#!/usr/bin/env python3
"""Build Vue 2 H5 assets and copy them to the PHP site's /app directory."""
import argparse
import shutil
import subprocess
from pathlib import Path

from bootstrap import ROOT


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--install', action='store_true', help='Install locked npm dependencies first')
    args = parser.parse_args()
    frontend = ROOT / 'uniapp'
    if not (frontend / 'public/runtime-config.js').is_file():
        parser.exit(1, 'Run python3 scripts/bootstrap.py first.\n')
    if args.install:
        subprocess.run(['npm', 'ci', '--legacy-peer-deps', '--no-audit', '--no-fund'], cwd=frontend, check=True)
    subprocess.run(['npm', 'run', 'build:h5'], cwd=frontend, check=True)
    built = frontend / 'dist/build/h5'
    if not (built / 'index.html').is_file():
        raise SystemExit('Build returned without an H5 entry file')
    shutil.copytree(built, ROOT / 'site/app', dirs_exist_ok=True)
    print('H5 build copied to site/app. Generated assets and runtime config remain ignored by Git.')


if __name__ == '__main__':
    main()
