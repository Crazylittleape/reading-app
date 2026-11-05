"""Container entrypoint lifecycle under the production capability set.

Runs the built web image with the same hardening as compose.yml (read-only root,
cap_drop ALL + SETUID/SETGID/CHOWN, no-new-privileges) against named volumes
that this test creates and removes itself. Enable with:

    READING_LIFECYCLE_IT=1 [READING_LIFECYCLE_IMAGE=reading-app-dev-php:local] \
    python3 -m unittest tests.test_container_lifecycle
"""
import os
import subprocess
import tempfile
import unittest
import uuid
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE_COMMIT = '24e5e5abccad37f3b41afee83934f8242ac36d21'
STAGE = '0123456789abcdef0123456789abcdef'


@unittest.skipUnless(os.environ.get('READING_LIFECYCLE_IT') == '1', 'set READING_LIFECYCLE_IT=1 with Docker and the built image')
class EntrypointLifecycleTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.image = os.environ.get('READING_LIFECYCLE_IMAGE', 'reading-app-dev-php:local')
        cls.tmp = tempfile.TemporaryDirectory()
        baseline = subprocess.run(['git', 'show', BASE_COMMIT + ':scripts/container-entrypoint.sh'], cwd=ROOT,
                                  capture_output=True, check=True).stdout
        cls.baseline = Path(cls.tmp.name) / 'baseline-entrypoint.sh'
        cls.baseline.write_bytes(baseline)

    @classmethod
    def tearDownClass(cls):
        cls.tmp.cleanup()

    def setUp(self):
        run = uuid.uuid4().hex[:10]
        self.volumes = ['reading-lifecycle-%s-cache' % run, 'reading-lifecycle-%s-uploads' % run]
        for name in self.volumes:
            subprocess.run(['docker', 'volume', 'create', '--label', 'reading-lifecycle-test=1', name], check=True, capture_output=True)

    def tearDown(self):
        # Only the volumes created by this test case are removed.
        subprocess.run(['docker', 'volume', 'rm', '-f'] + self.volumes, capture_output=True)

    def docker(self, *args, user=None, entrypoint=None, mounts=()):
        cmd = ['docker', 'run', '--rm', '--read-only', '--cap-drop', 'ALL', '--cap-add', 'SETUID', '--cap-add', 'SETGID',
               '--cap-add', 'CHOWN', '--security-opt', 'no-new-privileges:true', '--tmpfs', '/tmp',
               '-v', self.volumes[0] + ':/var/www/html/caches', '-v', self.volumes[1] + ':/var/www/html/attachment']
        for src, dst in mounts:
            cmd += ['-v', '%s:%s:ro' % (src, dst)]
        if user:
            cmd += ['--user', user]
        if entrypoint:
            cmd += ['--entrypoint', entrypoint]
        return subprocess.run(cmd + [self.image] + list(args), capture_output=True, text=True)

    def start(self):
        """One container start with the image's entrypoint; `true` replaces apache2-foreground."""
        return self.docker('true')

    def as_www(self, script):
        proc = self.docker('-c', script, user='www-data', entrypoint='sh')
        self.assertEqual(proc.returncode, 0, proc.stderr)
        return proc.stdout

    def assert_initialized(self):
        out = self.as_www('cd /var/www/html && stat -c "%U %a %n" caches caches/import caches/tpl attachment attachment/book/import'
                          ' && cat caches/install.lock && touch caches/import/.probe && rm caches/import/.probe')
        lines = out.splitlines()
        self.assertIn('www-data 700 caches/import', lines)
        for path in ('caches', 'caches/tpl', 'attachment', 'attachment/book/import'):
            self.assertTrue(any(line.startswith('www-data ') and line.endswith(' ' + path) for line in lines), (path, out))
        self.assertIn('installed', lines)

    def populate_staging(self):
        self.as_www('cd /var/www/html/caches/import && mkdir -p a7/%s/sec && chmod 700 a7 a7/%s a7/%s/sec'
                    ' && printf "{}" > a7/%s/meta.json && chmod 600 a7/%s/meta.json && echo keep > ../txt/existing.txt'
                    % ((STAGE,) * 5))

    def assert_staging_intact(self):
        out = self.as_www('cd /var/www/html/caches && stat -c "%U %a" import/a7/' + STAGE + '/meta.json && cat txt/existing.txt')
        self.assertEqual(out.split(), ['www-data', '600', 'keep'])

    def test_fresh_volume_then_restarts_with_private_staging(self):
        first = self.start()
        self.assertEqual(first.returncode, 0, first.stderr)
        self.assert_initialized()
        self.populate_staging()
        for _ in range(2):
            again = self.start()
            self.assertEqual(again.returncode, 0, again.stderr)
            self.assert_initialized()
            self.assert_staging_intact()

    def test_upgrade_cache_created_by_baseline_entrypoint(self):
        mounts = [(str(self.baseline), '/baseline-entrypoint.sh')]
        old = self.docker('/baseline-entrypoint.sh', 'true', entrypoint='sh', mounts=mounts)
        self.assertEqual(old.returncode, 0, old.stderr)
        self.as_www('echo keep > /var/www/html/caches/txt/existing.txt')
        # Reproduces the baseline defect: its second start cannot rewrite install.lock.
        old_restart = self.docker('/baseline-entrypoint.sh', 'true', entrypoint='sh', mounts=mounts)
        self.assertNotEqual(old_restart.returncode, 0)
        self.assertIn('Permission denied', old_restart.stderr)
        for _ in range(2):
            new = self.start()
            self.assertEqual(new.returncode, 0, new.stderr)
            self.assert_initialized()
        out = self.as_www('cat /var/www/html/caches/txt/existing.txt')
        self.assertEqual(out.strip(), 'keep')


if __name__ == '__main__':
    unittest.main()
