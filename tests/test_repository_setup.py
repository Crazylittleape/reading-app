import hashlib
import json
from pathlib import Path
import sys
import tempfile
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from bootstrap import bootstrap, read_env
from check_repository import inspect_file


class BootstrapTests(unittest.TestCase):
    def test_fresh_configuration_is_private_and_does_not_seed_books(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            values = bootstrap(root)
            self.assertEqual((root / '.env').stat().st_mode & 0o777, 0o600)
            self.assertGreaterEqual(len(values['APP_BOOK_KEY']), 32)
            seed = (root / 'db-init/90-local-bootstrap.sql').read_text()
            self.assertIn('INSERT INTO mc_admin', seed)
            self.assertNotIn('mc_book', seed)
            self.assertNotIn(values['ADMIN_PASSWORD'], seed)
            self.assertIn(hashlib.md5(values['ADMIN_PASSWORD'].encode()).hexdigest().encode().hex(), seed)
            public = (root / 'site/app/runtime-config.js').read_text()
            self.assertIn(values['APP_API_KEY'], public)
            for key in ['MYSQL_PASSWORD', 'APP_ENCRYPTION_KEY', 'APP_BOOK_KEY', 'ADMIN_PASSWORD', 'ADMIN_CODE']:
                self.assertNotIn(values[key], public)

    def test_rerun_preserves_secrets_and_updates_only_requested_origin(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            first = bootstrap(root)
            second = bootstrap(root, app_url='https://reader.example.invalid', app_name='Test Library')
            for key in first:
                if key not in ['APP_URL', 'APP_NAME']:
                    self.assertEqual(first[key], second[key])
            self.assertEqual(read_env(root / '.env')['APP_URL'], 'https://reader.example.invalid')
            native = json.loads((root / 'uniapp/runtime-config.json').read_text())
            self.assertEqual(native['apiBase'], 'https://reader.example.invalid')

    def test_rejects_untrusted_url_before_creating_credentials(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            for url in ['file:///etc/passwd', 'https://user:pass@example.invalid', 'https://example.invalid/app/']:
                with self.assertRaises(ValueError):
                    bootstrap(root, app_url=url)
            self.assertFalse((root / '.env').exists())


class RepositoryHygieneTests(unittest.TestCase):
    def test_rejects_private_paths_even_if_forced_into_git(self):
        for name in ['.env', 'a/secrets.json', 'deployment.json', 'site/caches/txt/2/secret.txt',
                     'site/attachment/book/import/a/cover.jpg', 'backup.sql', 'db-init/90-local-bootstrap.sql']:
            self.assertTrue(inspect_file(name, b''), name)

    def test_schema_must_not_contain_rows(self):
        self.assertEqual(inspect_file('db-init/01-schema.sql', b'CREATE TABLE example (id INT);'), [])
        self.assertTrue(inspect_file('db-init/01-schema.sql', b'INSERT INTO example VALUES (1);'))

    def test_detects_inline_secret_but_allows_environment_lookup(self):
        php = "define('Admin_Code', 'a-real-secret-value');".encode()
        self.assertTrue(inspect_file('site/sys/libs/config.php', php))
        safe = "define('Admin_Code', reading_required_env('ADMIN_CODE'));".encode()
        self.assertEqual(inspect_file('site/sys/libs/config.php', safe), [])


if __name__ == '__main__':
    unittest.main()
