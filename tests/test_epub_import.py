"""EPUB import batch 1: static guarantees plus HTTP integration tests.

The integration class drives the real admin endpoints of a running, isolated
Compose stack. Enable it with:

    READING_IMPORT_IT=1 READING_IMPORT_BASE_URL=http://127.0.0.1:18187 \
    READING_IMPORT_COMPOSE_PROJECT=reading-app-batch1-test python3 -m unittest tests.test_epub_import

Fixtures are generated in memory (tests/epub_fixtures.py); nothing is committed.
"""
import html
import ipaddress
import socket
import http.cookiejar
import io
import json
import os
import re
import subprocess
import sys
import unittest
import threading
import time
import uuid
import warnings
from concurrent.futures import ThreadPoolExecutor
import zipfile
import zlib
from pathlib import Path
from urllib.error import HTTPError
from urllib.parse import urlencode, urlparse
from urllib.request import HTTPCookieProcessor, ProxyHandler, Request, build_opener

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))
import epub_fixtures as fx  # noqa: E402

MIB = 1024 * 1024


def php_limits():
    text = (ROOT / 'site/sys/apps/config/epub_import.php').read_text()
    return {k: int(v) for k, v in re.findall(r"'(\w+)' => (\d+)", text)}


class StaticImportTests(unittest.TestCase):
    def test_centralized_limits_match_specification(self):
        limits = php_limits()
        self.assertEqual(limits['max_upload_bytes'], 10 * MIB)
        self.assertEqual(limits['max_entries'], 2000)
        self.assertEqual(limits['max_total_bytes'], 100 * MIB)
        self.assertEqual(limits['max_entry_bytes'], 10 * MIB)

    def test_parser_complexity_budgets_are_centralized(self):
        limits = php_limits()
        self.assertEqual((limits['max_xml_tags'], limits['max_section_blocks'], limits['max_book_blocks']), (200000, 20000, 200000))
        self.assertEqual(limits['max_decode_bytes'], 128 * MIB)
        parser = (ROOT / 'site/sys/apps/libraries/Epub_parser.php').read_text()
        self.assertNotIn('preg_split', parser)
        self.assertNotIn('memory_limit', parser)

    def test_isolation_guard_rejects_shared_projects_and_remote_targets(self):
        for project in ('reading-app', 'reading-app-dev', 'Bad Name'):
            self.assertIn('refusing compose project', isolation_problem('http://localhost:18187', project))
        for base in ('http://example.com:18187', 'https://localhost:18187', 'http://localhost', 'http://10.0.0.5:18187'):
            self.assertIn('READING_IMPORT_BASE_URL', isolation_problem(base, 'reading-app-batch1-test'))

    def test_binding_guard_matches_address_and_family(self):
        literal = lambda host, port: {ipaddress.ip_address(host)}
        v4 = ['127.0.0.1:18187']
        self.assertIsNone(binding_problem('http://127.0.0.1:18187', v4, literal))
        self.assertIsNone(binding_problem('http://[::1]:18187', ['[::1]:18187'], literal))
        self.assertIsNotNone(binding_problem('http://[::1]:18187', v4, literal))
        self.assertIsNotNone(binding_problem('http://127.0.0.1:18187', ['[::1]:18187'], literal))
        self.assertIsNotNone(binding_problem('http://127.0.0.1:18188', v4, literal))
        self.assertIsNotNone(binding_problem('http://127.0.0.1:18187', ['0.0.0.0:18187'], literal))
        self.assertIsNotNone(binding_problem('http://127.0.0.1:18187', [], literal))
        both = lambda host, port: {ipaddress.ip_address('127.0.0.1'), ipaddress.ip_address('::1')}
        self.assertIsNotNone(binding_problem('http://localhost:18187', v4, both))
        self.assertIsNone(binding_problem('http://localhost:18187', v4, lambda host, port: {ipaddress.ip_address('127.0.0.1')}))

    def test_server_request_limits_allow_the_application_limit(self):
        def mib(value):
            return int(value[:-1]) * MIB if value[-1] in 'Mm' else int(value)
        ini = dict(re.findall(r'^(\w+)\s*=\s*(\S+)', (ROOT / 'php.ini').read_text(), re.M))
        apache = int(re.search(r'LimitRequestBody (\d+)', (ROOT / 'apache.conf').read_text()).group(1))
        nginx = mib(re.search(r'client_max_body_size (\w+);', (ROOT / 'nginx.conf').read_text()).group(1))
        need = php_limits()['max_upload_bytes'] + 64 * 1024
        for name, value in [('upload_max_filesize', mib(ini['upload_max_filesize'])), ('post_max_size', mib(ini['post_max_size'])),
                            ('apache', apache), ('nginx', nginx)]:
            self.assertGreaterEqual(value, need, name)

    def test_docker_installs_zip_and_verifies_required_modules(self):
        dockerfile = (ROOT / 'Dockerfile').read_text()
        self.assertIn('libzip-dev', dockerfile)
        self.assertRegex(dockerfile, r'docker-php-ext-install[^\n]*\bzip\b')
        for module in ('dom', 'libxml', 'xmlreader', 'fileinfo', 'mbstring', 'zip'):
            self.assertIn('"%s"' % module, dockerfile)
        compose = (ROOT / 'compose.yml').read_text()
        self.assertIn('./site:/var/www/html:ro', compose)
        self.assertIn('read_only: true', compose)
        self.assertIn('${BIND_ADDRESS:-127.0.0.1}', compose)
        self.assertIn('name: reading-app-dev', compose)

    def test_staging_is_private_cache_directory(self):
        entry = (ROOT / 'scripts/container-entrypoint.sh').read_text()
        self.assertRegex(entry, r"directories='[^']*\bimport\b")
        self.assertIn('chmod 0700 "$1/import"', entry)
        self.assertIn('setpriv --reuid=www-data --regid=www-data', entry)
        self.assertNotIn('chown -R', entry)
        staging = (ROOT / 'site/sys/apps/libraries/Import_staging.php').read_text()
        self.assertIn("FCPATH.'caches/import/'", staging)
        self.assertNotIn('attachment', staging)

    def test_every_endpoint_authenticates_and_writes_need_post_and_csrf(self):
        source = (ROOT / 'site/sys/apps/controllers/admin/Import.php').read_text()
        constructor = source.split('function __construct', 1)[1].split('public function index', 1)[0]
        self.assertIn('$this->admin->operator(', constructor)
        for name in ('upload', 'save', 'delete', 'cleanup'):
            body = source.split('public function %s(' % name, 1)[1].split('public function', 1)[0]
            self.assertIn('$this->require_post();', body, name)
            self.assertIn('$this->require_csrf();', body, name)
        self.assertNotRegex(source, r'(?i)insert|->db->|book_chapter')
        self.assertIn("'import/index", (ROOT / 'site/sys/libs/nav.php').read_text())

    def test_preview_never_renders_markup(self):
        for name in ('index.tpl', 'preview.tpl'):
            text = (ROOT / 'site/template/admin/import' / name).read_text()
            self.assertNotIn('v-html', text)
            self.assertNotRegex(text, r'\.html\(|innerHTML|insertAdjacentHTML|document\.write')
        # Section edits are one JSON field, not two form variables per section (max_input_vars).
        self.assertNotIn('section_title[', (ROOT / 'site/template/admin/import/preview.tpl').read_text())

    def test_global_csrf_stays_disabled(self):
        config = (ROOT / 'site/sys/apps/config/config.php').read_text()
        self.assertIn("$config['csrf_protection'] = FALSE;", config)

    def test_fixtures_are_valid_zip_files(self):
        for data in (fx.epub3_full(), fx.epub2_full(), fx.minimal()):
            with zipfile.ZipFile(io.BytesIO(data)) as zf:
                self.assertIsNone(zf.testzip())
                self.assertEqual(zf.namelist()[0], 'mimetype')


def read_env():
    values = {}
    for line in (ROOT / '.env').read_text().splitlines():
        if '=' in line and not line.startswith('#'):
            key, value = line.split('=', 1)
            values[key] = value
    return values


class Client:
    def __init__(self, base):
        self.base = base
        self.jar = http.cookiejar.CookieJar()
        self.opener = build_opener(ProxyHandler({}), HTTPCookieProcessor(self.jar))

    def request(self, path, data=None, method=None, headers=None, files=None):
        body = None
        hdrs = {'Accept': 'application/json'}
        if files is not None:
            boundary = uuid.uuid4().hex
            parts = []
            for key, value in (data or {}).items():
                value = value if isinstance(value, bytes) else str(value).encode()
                parts.append(('--%s\r\nContent-Disposition: form-data; name="%s"\r\n\r\n' % (boundary, key)).encode() + value + b'\r\n')
            for key, (filename, content) in files.items():
                parts.append(('--%s\r\nContent-Disposition: form-data; name="%s"; filename="%s"\r\n'
                              'Content-Type: application/epub+zip\r\n\r\n' % (boundary, key, filename)).encode() + content + b'\r\n')
            parts.append(('--%s--\r\n' % boundary).encode())
            body = b''.join(parts)
            hdrs['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        elif data is not None:
            body = urlencode(data, doseq=True).encode()
            hdrs['Content-Type'] = 'application/x-www-form-urlencoded'
        hdrs.update(headers or {})
        req = Request(self.base + path, data=body, headers=hdrs, method=method)
        try:
            with self.opener.open(req, timeout=60) as response:
                return response.status, dict(response.headers), response.read()
        except HTTPError as error:
            return error.code, dict(error.headers), error.read()

    def json(self, *args, **kwargs):
        status, headers, content = self.request(*args, **kwargs)
        try:
            return status, json.loads(content)
        except ValueError:
            raise AssertionError('non-JSON response %s: %r' % (status, content[:500]))


LOCAL_HOSTS = {'localhost', '127.0.0.1', '::1'}
LOOPBACK_BINDINGS = {'127.0.0.1', '::1', '[::1]'}
# The source checkout's own project and the documented default development stack are never disposable.
SHARED_PROJECTS = {'reading-app', 'reading-app-dev'}


def compose_run(project, *args, stdin=None, check=True):
    """Run docker compose without secrets in argv; failures never echo the environment."""
    proc = subprocess.run(['docker', 'compose', '-p', project] + list(args), cwd=ROOT, input=stdin,
                          capture_output=True, text=True)
    if check and proc.returncode:
        raise AssertionError('docker compose %s failed (exit %d): %s' % (args[0], proc.returncode, proc.stderr.strip()[-400:]))
    return proc


def isolation_problem(base, project):
    """Why base/project is not a disposable local stack of this checkout, or None. Only read-only docker queries."""
    url = urlparse(base)
    if url.scheme != 'http' or url.hostname not in LOCAL_HOSTS or url.port is None or url.path not in ('', '/'):
        return 'READING_IMPORT_BASE_URL must be http://localhost:<port> (got %r)' % base
    if not re.fullmatch(r'[a-z0-9][a-z0-9_-]*', project) or project in SHARED_PROJECTS:
        return 'refusing compose project %r; use a disposable project name' % project
    web = compose_run(project, 'ps', '-q', 'web', check=False).stdout.split()
    if len(web) != 1:
        return 'compose project %r has no single running web container' % project
    inspect = subprocess.run(['docker', 'inspect', '-f', '{{json .Config.Labels}}', web[0]], capture_output=True, text=True)
    labels = json.loads(inspect.stdout) if inspect.returncode == 0 else {}
    if labels.get('com.docker.compose.project') != project or labels.get('com.docker.compose.project.working_dir') != str(ROOT):
        return 'web container %s is not labelled as compose project %r of %s' % (web[0][:12], project, ROOT)
    return binding_problem(base, compose_run(project, 'port', 'gateway', '8080', check=False).stdout.split())


def resolved_addresses(host, port):
    return {ipaddress.ip_address(info[4][0]) for info in socket.getaddrinfo(host, port, type=socket.SOCK_STREAM)}


def binding_problem(base, bindings, resolve=resolved_addresses):
    """Every address the client may connect to for base must be one the selected project publishes on loopback."""
    url = urlparse(base)
    published = set()
    for binding in bindings:
        host, _, port = binding.rpartition(':')
        try:
            address = ipaddress.ip_address(host.strip('[]'))
        except ValueError:
            return 'unparseable gateway binding %r' % binding
        if not address.is_loopback or port != str(url.port):
            return 'gateway binding %s is not loopback port %d' % (binding, url.port)
        published.add(address)
    if not published:
        return 'gateway publishes no port for this project'
    try:
        targets = resolve(url.hostname, url.port)
    except OSError as exc:
        return 'cannot resolve %s: %s' % (url.hostname, exc)
    if not targets or not targets <= published:
        return '%s connects to %s but the project publishes only %s (address and family must match)' % (
            base, ', '.join(sorted(map(str, targets))) or 'nothing', ', '.join(sorted(map(str, published))))
    return None


@unittest.skipUnless(os.environ.get('READING_IMPORT_IT') == '1', 'set READING_IMPORT_IT=1 with a running isolated stack')
class ImportIntegrationTests(unittest.TestCase):
    """Drives the real endpoints. Every destructive step is limited to admins, categories and
    staging directories created by this run, on a local, explicitly disposable Compose project."""

    @classmethod
    def setUpClass(cls):
        cls.base = os.environ.get('READING_IMPORT_BASE_URL', 'http://127.0.0.1:18187').rstrip('/')
        cls.project = os.environ.get('READING_IMPORT_COMPOSE_PROJECT', 'reading-app-batch1-test')
        # Explicitly enabled runs fail loudly on an unsafe target; nothing is written before this check.
        problem = isolation_problem(cls.base, cls.project)
        if problem:
            raise AssertionError('READING_IMPORT_IT=1 but the target is not an isolated stack: ' + problem)
        cls.env = read_env()
        cls.run_id = uuid.uuid4().hex[:10]
        cls.prefix = 'it%s_' % cls.run_id
        cls.owned_admins = []
        cls.cid = int(cls.sql("INSERT INTO mc_book_class (name,yname,fid,xid) VALUES ('测试分类','%scls',0,1);"
                              "SELECT LAST_INSERT_ID();" % cls.prefix))
        cls.books_before = cls.counts()
        cls.admin_name, cls.admin_pass, cls.admin_id = cls.make_admin('main')
        cls.admin = cls.login(cls.admin_name, cls.admin_pass)
        cls.other = cls.login(*cls.make_admin('other')[:2])

    @classmethod
    def tearDownClass(cls):
        cls.purge_owned_staging()
        cls.sql("DELETE FROM mc_admin WHERE name LIKE '%s%%'; DELETE FROM mc_book_class WHERE yname='%scls'" % (cls.prefix, cls.prefix))

    def tearDown(self):
        self.purge_owned_staging()

    @classmethod
    def make_admin(cls, tag):
        name, password = cls.prefix + tag + uuid.uuid4().hex[:4], uuid.uuid4().hex
        admin_id = int(cls.sql("INSERT INTO mc_admin (name,pass,nichen,sid) VALUES ('%s', MD5('%s'), 'it', 0);"
                               "SELECT LAST_INSERT_ID();" % (name, password)))
        cls.owned_admins.append(admin_id)
        return name, password, admin_id

    @classmethod
    def purge_owned_staging(cls):
        paths = ' '.join('/var/www/html/caches/import/a%d' % int(i) for i in cls.owned_admins)
        if paths:
            cls.compose('exec', '-T', '-u', 'www-data', 'web', 'rm', '-rf', *paths.split())

    @classmethod
    def compose(cls, *args, stdin=None):
        return compose_run(cls.project, *args, stdin=stdin).stdout

    @classmethod
    def sql(cls, statement):
        # Credentials come from the db container's own environment; nothing secret is placed in argv.
        script = 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -N -B --default-character-set=utf8mb4 -u"$MYSQL_USER" "$MYSQL_DATABASE"'
        return cls.compose('exec', '-T', 'db', 'sh', '-c', script, stdin=statement).strip()

    @classmethod
    def counts(cls):
        tables = cls.sql("SELECT COUNT(*) FROM mc_book") + '/' + cls.sql(
            "SELECT IFNULL(SUM(TABLE_ROWS),0) FROM information_schema.TABLES WHERE TABLE_SCHEMA='reading_app' AND TABLE_NAME LIKE 'mc_book_chapter%'")
        files = cls.compose('exec', '-T', 'web', 'find', '/var/www/html/attachment', '-type', 'f').count('\n')
        return tables, files

    @classmethod
    def login(cls, name, password):
        client = Client(cls.base)
        status, body = client.json('/manage.php/login/save', {'name': name, 'pass': password, 'code': cls.env['ADMIN_CODE']})
        assert status == 200 and body['code'] == 1, body
        return client

    def setUp(self):
        warnings.simplefilter('ignore', UserWarning)  # zipfile warns while building duplicate-name fixtures

    def csrf(self, client):
        status, headers, content = client.request('/manage.php/import/index', headers={'Accept': 'text/html'})
        self.assertEqual(status, 200)
        return re.search(rb'name="csrf_token" value="([0-9]+\.[0-9a-f]{64})"', content).group(1).decode()

    def upload(self, data, client=None, name='synthetic.epub', token=None):
        client = client or self.admin
        token = self.csrf(client) if token is None else token
        return client.json('/manage.php/import/upload', {'csrf_token': token}, files={'epub': (name, data)})

    def staged(self, data, client=None):
        client = client or self.admin
        status, body = self.upload(data, client=client)
        self.assertEqual(status, 200, body)
        self.assertEqual(body['code'], 1, body)
        status, stage = client.json('/manage.php/import/status/' + body['id'])
        self.assertEqual(status, 200)
        return stage['stage']

    def rejected(self, data, reason, status=422, **kwargs):
        code, body = self.upload(data, **kwargs)
        self.assertEqual((code, body.get('reason')), (status, reason), body)
        return body

    def section(self, stage, n, client=None):
        status, body = (client or self.admin).json('/manage.php/import/section/%s/%d' % (stage['id'], n))
        self.assertEqual(status, 200, body)
        return body['blocks']

    def save_form(self, stage, token, **override):
        sections = [{'title': s['title'], 'include': s['include']} for s in stage['sections']]
        form = {'csrf_token': token, 'title': 'T', 'author': 'A', 'cid': str(self.cid), 'sections': json.dumps(sections)}
        form.update(override)
        return form

    def stage_count(self, client):
        page = client.request('/manage.php/import/index', headers={'Accept': 'text/html'})[2]
        return page.count(b'epub-delete" data-id=')

    # ---- legal books ------------------------------------------------------------

    def test_epub3_spine_navigation_images_and_escaping(self):
        stage = self.staged(fx.epub3_full())
        self.assertEqual(stage['version'], '3.0')
        self.assertEqual(stage['title'], '合成测试书 & 卷一')
        self.assertEqual(stage['author'], '测试作者')
        self.assertEqual(stage['language'], 'zh-CN')
        sections = stage['sections']
        self.assertEqual([s['source'] for s in sections], [
            'OEBPS/cover.xhtml', 'OEBPS/nav.xhtml', 'OEBPS/text/前言.xhtml', 'OEBPS/text/第一章.xhtml',
            'OEBPS/text/part 2.xhtml', 'OEBPS/text/plate.xhtml', 'OEBPS/text/appendix.xhtml'])
        self.assertEqual([s['kind'] for s in sections], ['cover', 'nav', 'text', 'text', 'text', 'image', 'text'])
        self.assertEqual([s['include'] for s in sections], [False, False, True, True, True, True, True])
        self.assertEqual([s['title'] for s in sections[2:]], ['前言', '第一章 起点', '未命名小节 5', '未命名小节 6', '附录 A'])
        self.assertEqual([s['title_source'] for s in sections[2:]], ['nav', 'nav', 'fallback', 'fallback', 'heading'])
        self.assertTrue(stage['cover'].endswith('/import/image/%s/0' % stage['id']))
        self.assertEqual(self.section(stage, 2), [{'type': 'text', 'text': '前言'}, {'type': 'text', 'text': '这是前言\u00a0内容 & 说明。'}])
        self.assertEqual([b['text'] for b in self.section(stage, 3)], ['第一章', '第一段。', '第二段', '换行。'])
        plate = self.section(stage, 5)
        self.assertEqual(len(plate), 1)
        self.assertEqual(plate[0]['type'], 'image')
        status, headers, content = self.admin.request(plate[0]['url'][plate[0]['url'].index('/manage.php'):])
        self.assertEqual(status, 200)
        self.assertEqual(headers['Content-Type'], 'image/gif')
        self.assertEqual(headers['X-Content-Type-Options'], 'nosniff')
        self.assertEqual(content, fx.gif())
        self.assertEqual(self.counts(), self.books_before)

    def test_epub2_ncx_guide_cover_and_roles(self):
        stage = self.staged(fx.epub2_full())
        self.assertEqual((stage['version'], stage['title'], stage['author'], stage['language']), ('2.0', 'Synthetic Two', 'Ada Writer', 'en'))
        self.assertEqual([(s['kind'], s['include'], s['title']) for s in stage['sections']],
                         [('cover', False, '未命名小节 1'), ('text', True, 'Chapter One'), ('text', True, 'Chapter & Two')])
        self.assertEqual([b['text'] for b in self.section(stage, 1)], ['It was <synthetic> text.', 'line one', 'line two'])
        self.assertNotEqual(stage['cover'], '')

    def test_edit_preview_metadata_and_titles(self):
        stage = self.staged(fx.epub2_full())
        token = self.csrf(self.admin)
        url = '/manage.php/import/save/' + stage['id']
        edits = [{'title': '封面', 'include': True}, {'title': '第一回', 'include': False}, {'title': '第二回', 'include': True}]
        form = self.save_form(stage, token, title=' 新书名 ', author='新作者', sections=json.dumps(edits))
        status, body = self.admin.json(url, form)
        self.assertEqual((status, body['code']), (200, 1), body)
        status, after = self.admin.json('/manage.php/import/status/' + stage['id'])
        after = after['stage']
        self.assertEqual((after['title'], after['author'], after['cid']), ('新书名', '新作者', self.cid))
        self.assertEqual([(s['title'], s['include']) for s in after['sections']], [('封面', True), ('第一回', False), ('第二回', True)])
        self.assertEqual(after['sections'][1]['auto_title'], 'Chapter One')
        bad = dict(form, cid='999999')
        self.assertEqual(self.admin.json(url, bad)[0], 422)
        bad = dict(form, title='')
        self.assertEqual(self.admin.json(url, bad)[0], 422)
        self.assertEqual(self.admin.json(url, dict(form, csrf_token='1.' + '0' * 64))[0], 403)
        for broken in (json.dumps(edits[:2]), json.dumps([dict(e, include='1') for e in edits]), '{"0": 1}', 'not json',
                       json.dumps([{'title': 'x' * 129, 'include': True}] * 3)):
            self.assertEqual(self.admin.json(url, dict(form, sections=broken))[0], 422, broken[:40])
        self.assertEqual(self.admin.json(url, method='GET')[0], 405)
        self.assertEqual(self.counts(), self.books_before)

    def test_metadata_is_stored_raw_and_escaped_only_on_output(self):
        stage = self.staged(fx.minimal())
        url = '/manage.php/import/save/' + stage['id']
        cases = [('界面验收 <书名> & 测试', "O'Connor \"引号\""),
                 ('&lt;b&gt; &amp; &#39; &quot; literal', "it's <i>&amp;</i>"),
                 ('x' * 128, 'y' * 64)]
        for title, author in cases:
            with self.subTest(title=title[:20]):
                status, body = self.admin.json(url, self.save_form(stage, self.csrf(self.admin), title=title, author=author))
                self.assertEqual((status, body['code']), (200, 1), body)
                status, after = self.admin.json('/manage.php/import/status/' + stage['id'])
                self.assertEqual((after['stage']['title'], after['stage']['author']), (title, author))
                page = self.admin.request('/manage.php/import/index', headers={'Accept': 'text/html'})[2].decode()
                self.assertIn('<td>%s</td>' % html.escape(title, quote=True).replace('&#x27;', '&#039;'), page)
        # Reloading the preview after a second save still returns the exact text (no cumulative encoding).
        status, after = self.admin.json('/manage.php/import/status/' + stage['id'])
        self.assertEqual(after['stage']['title'], 'x' * 128)
        token = self.csrf(self.admin)
        for field, value in (('title', 'x' * 129), ('author', 'y' * 65), ('title', b'\xff'), ('title', b'A\xffB'),
                             ('author', b'O\xffConnor'), ('author', 'O'.encode() + b'\xc3' + b'Connor')):
            form = self.save_form(stage, token, **{field: value})
            self.assertEqual(self.admin.json(url, form)[0], 422, field)
        for field in ('title', 'author'):
            form = self.save_form(stage, token)
            form[field + '[]'] = form.pop(field)
            self.assertEqual(self.admin.json(url, form)[0], 422, field)
        status, after = self.admin.json('/manage.php/import/status/' + stage['id'])
        self.assertEqual((after['stage']['title'], after['stage']['author']), ('x' * 128, 'y' * 64))

    def test_save_rejects_non_urlencoded_bodies_without_changes(self):
        stage = self.staged(fx.minimal())
        url = '/manage.php/import/save/' + stage['id']
        token = self.csrf(self.admin)
        sections = json.dumps([{'title': '@@', 'include': s['include']} for s in stage['sections']]).encode()
        bad = {'title': b'A\xffB', 'author': b'O\xffConnor', 'sections': sections.replace(b'@@', b'A\xffB')}
        for field, value in list(bad.items()) + [(None, None)]:
            form = self.save_form(stage, token, **({field: value} if field else {}))
            with self.subTest(field=field):
                status, body = self.admin.json(url, form, files={})
                self.assertEqual((status, body.get('reason')), (415, 'content_type'), body)
                if field:
                    self.assertEqual(self.admin.json(url, form)[0], 422, field)
        raw = urlencode(self.save_form(stage, token)).encode()
        for kind in ('text/plain', 'application/json', 'application/x-www-form-urlencoded-x'):
            with self.subTest(kind=kind):
                req = Request(self.base + url, data=raw, method='POST', headers={'Content-Type': kind})
                try:
                    with self.admin.opener.open(req, timeout=60) as response:
                        status = response.status
                except HTTPError as error:
                    status = error.code
                self.assertEqual(status, 415, kind)
        status, after = self.admin.json('/manage.php/import/status/' + stage['id'])
        self.assertEqual((after['stage']['title'], after['stage']['author']), (stage['title'], stage['author']))
        self.assertEqual(after['stage']['sections'], stage['sections'])
        self.assertEqual(self.admin.json(url, self.save_form(stage, token, title='OK'))[0], 200)

    # ---- authentication and isolation ------------------------------------------

    def test_unauthenticated_requests_are_rejected(self):
        anon = Client(self.base)
        stage = self.staged(fx.minimal())
        for path in ('/manage.php/import/status/' + stage['id'], '/manage.php/import/section/%s/0' % stage['id'],
                     '/manage.php/import/image/%s/0' % stage['id']):
            self.assertEqual(anon.json(path)[0], 401, path)
        for path in ('/manage.php/import/upload', '/manage.php/import/save/' + stage['id'], '/manage.php/import/delete', '/manage.php/import/cleanup'):
            self.assertEqual(anon.json(path, {'id': stage['id'], 'csrf_token': 'x'})[0], 401, path)
        status, headers, content = anon.request('/manage.php/import/index', headers={'Accept': 'text/html'})
        self.assertEqual(status, 401)
        self.assertIn(b'login', content)

    def test_writes_require_post_and_valid_csrf(self):
        self.assertEqual(self.admin.json('/manage.php/import/upload')[0], 405)
        self.assertEqual(self.admin.json('/manage.php/import/cleanup')[0], 405)
        self.assertEqual(self.admin.json('/manage.php/import/delete?id=0')[0], 405)
        self.rejected(fx.minimal(), 'csrf', status=403, token='')
        self.rejected(fx.minimal(), 'csrf', status=403, token='bogus')
        other_token = self.csrf(self.other)
        self.rejected(fx.minimal(), 'csrf', status=403, token=other_token)
        self.assertEqual(self.admin.json('/manage.php/import/cleanup', {})[0], 403)

    def test_cross_admin_access_is_denied(self):
        stage = self.staged(fx.epub3_full())
        token = self.csrf(self.other)
        sid = stage['id']
        for path in ('/manage.php/import/status/' + sid, '/manage.php/import/section/%s/2' % sid, '/manage.php/import/image/%s/0' % sid):
            self.assertEqual(self.other.json(path)[0], 404, path)
        self.assertEqual(self.other.request('/manage.php/import/preview/' + sid, headers={'Accept': 'text/html'})[0], 404)
        self.assertEqual(self.other.json('/manage.php/import/save/' + sid, {'csrf_token': token, 'title': 'x'})[0], 404)
        self.assertEqual(self.other.json('/manage.php/import/delete', {'csrf_token': token, 'id': sid})[0], 404)
        self.assertEqual(self.admin.json('/manage.php/import/status/' + sid)[0], 200)

    def test_disabled_and_deleted_admin_cookies_are_rejected(self):
        name, password, _ = self.make_admin('disabled')
        client = self.login(name, password)
        status, body = self.upload(fx.minimal(), client=client)
        self.assertEqual(status, 200, body)
        path = '/manage.php/import/status/' + body['id']
        self.assertEqual(client.json(path)[0], 200)
        self.sql("UPDATE mc_admin SET sid=1 WHERE name='%s'" % name)
        self.assertEqual(client.json(path)[0], 401)
        # an unrelated admin is unaffected
        self.assertEqual(self.admin.json('/manage.php/import/status/' + self.staged(fx.minimal())['id'])[0], 200)
        name2, password2, _ = self.make_admin('deleted')
        deleted = self.login(name2, password2)
        token = self.csrf(deleted)
        self.sql("DELETE FROM mc_admin WHERE name='%s'" % name2)
        self.assertEqual(deleted.json('/manage.php/import/upload', {'csrf_token': token})[0], 401)

    def test_delete_expiry_and_cleanup(self):
        stage = self.staged(fx.minimal())
        token = self.csrf(self.admin)
        status, body = self.admin.json('/manage.php/import/delete', {'csrf_token': token, 'id': stage['id']})
        self.assertEqual((status, body['code']), (200, 1))
        self.assertEqual(self.admin.json('/manage.php/import/status/' + stage['id'])[0], 404)
        stage = self.staged(fx.minimal())
        meta = '/var/www/html/caches/import/a%d/%s/meta.json' % (self.admin_id, stage['id'])
        self.compose('exec', '-T', '-u', 'www-data', 'web', 'php', '-r',
                     '$m=json_decode(file_get_contents($argv[1]),true);$m["expires"]=time()-5;'
                     'file_put_contents($argv[1],json_encode($m));', meta)
        status, body = self.admin.json('/manage.php/import/cleanup', {'csrf_token': token})
        self.assertEqual((status, body['code']), (200, 1))
        self.assertGreaterEqual(body['removed'], 1)
        self.assertEqual(self.admin.json('/manage.php/import/status/' + stage['id'])[0], 404)

    def test_pages_escape_epub_metadata(self):
        opf = fx.opf3('<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>', '<itemref idref="c1"/>',
                      title='&lt;img src=x onerror=alert(1)&gt;', author='&lt;/script&gt;')
        stage = self.staged(fx.minimal(opf_override=opf))
        self.assertEqual(stage['title'], '<img src=x onerror=alert(1)>')
        status, _, page = self.admin.request('/manage.php/import/index', headers={'Accept': 'text/html'})
        self.assertEqual(status, 200)
        self.assertIn('&lt;img src=x onerror=alert(1)&gt;'.encode(), page)
        self.assertNotIn(b'<img src=x', page)
        status, _, page = self.admin.request('/manage.php/import/preview/' + stage['id'], headers={'Accept': 'text/html'})
        self.assertEqual(status, 200)
        self.assertIn(('data-id="%s"' % stage['id']).encode(), page)
        self.assertIn('测试分类'.encode(), page)
        status, headers, raw = self.admin.request('/manage.php/import/status/' + stage['id'])
        self.assertNotIn(b'<img', raw)
        self.assertNotIn(b'</script', raw)

    def test_staging_quota_per_admin(self):
        client = self.login(*self.make_admin('quota')[:2])
        for _ in range(php_limits()['staging_max_per_admin']):
            self.staged(fx.minimal(), client=client)
        self.rejected(fx.minimal(), 'quota', status=409, client=client)
        self.assertEqual(self.upload(fx.minimal(), client=self.other)[0], 200)

    def test_staging_is_not_publicly_reachable(self):
        stage = self.staged(fx.epub3_full())
        anon = Client(self.base)
        for path in ('/caches/import/', '/caches/import/a%d/%s/meta.json' % (self.admin_id, stage['id'])):
            self.assertIn(anon.request(path)[0], (403, 404), path)
        self.assertEqual(self.counts(), self.books_before)

    # ---- review fixes: image-only/nav-only books, long previews, concurrency, restart

    def test_single_image_page_without_cover_metadata(self):
        stage = self.staged(fx.image_only_single())
        self.assertEqual(len(stage['sections']), 1)
        section = stage['sections'][0]
        self.assertEqual((section['kind'], section['include'], section['images']), ('image', True, 1))
        self.assertEqual(self.section(stage, 0)[0]['type'], 'image')
        status, body = self.admin.json('/manage.php/import/save/' + stage['id'], self.save_form(stage, self.csrf(self.admin)))
        self.assertEqual((status, body['code']), (200, 1), body)

    def test_sole_nav_document_with_body_text(self):
        stage = self.staged(fx.nav_only_with_body())
        section = stage['sections'][0]
        self.assertEqual((section['kind'], section['include']), ('nav', True))
        self.assertIn('导航文件里的正文段落。', [b.get('text') for b in self.section(stage, 0)])
        self.assertTrue(any('默认保留' in w for w in stage['warnings']))

    def test_save_600_sections_over_http(self):
        stage = self.staged(fx.many_sections(600))
        self.assertEqual(len(stage['sections']), 600)
        edits = [{'title': '改%d' % i, 'include': i % 3 != 0} for i in range(600)]
        status, body = self.admin.json('/manage.php/import/save/' + stage['id'],
                                       self.save_form(stage, self.csrf(self.admin), sections=json.dumps(edits)))
        self.assertEqual((status, body['code']), (200, 1), body)
        after = self.admin.json('/manage.php/import/status/' + stage['id'])[1]['stage']['sections']
        self.assertEqual([(s['title'], s['include']) for s in after], [(e['title'], e['include']) for e in edits])

    def test_section_boundary_is_bounded_by_entry_limit(self):
        # mimetype + container.xml + OPF leave max_entries - 3 spine documents.
        largest = php_limits()['max_entries'] - 3
        stage = self.staged(fx.many_sections(largest))
        self.assertEqual(len(stage['sections']), largest)
        edits = [{'title': 'S%d' % i, 'include': True} for i in range(largest)]
        status, body = self.admin.json('/manage.php/import/save/' + stage['id'],
                                       self.save_form(stage, self.csrf(self.admin), sections=json.dumps(edits)))
        self.assertEqual((status, body['code']), (200, 1), body)
        self.assertEqual(self.admin.json('/manage.php/import/status/' + stage['id'])[1]['stage']['sections'][-1]['title'], 'S%d' % (largest - 1))
        self.rejected(fx.many_sections(largest + 1), 'too_many_entries')

    def test_parallel_uploads_cannot_exceed_quota(self):
        client = self.login(*self.make_admin('race')[:2])
        limit = php_limits()['staging_max_per_admin']
        for _ in range(limit - 1):
            self.staged(fx.minimal(), client=client)
        token = self.csrf(client)
        data = fx.epub3_full()
        with ThreadPoolExecutor(max_workers=6) as pool:
            results = list(pool.map(lambda _: self.upload(data, client=client, token=token), range(6)))
        codes = sorted(code for code, _ in results)
        self.assertEqual(codes, [200] + [409] * 5, results)
        self.assertEqual(self.stage_count(client), limit)

    def test_save_and_delete_race_never_leaves_partial_preview(self):
        client = self.login(*self.make_admin('savedel')[:2])
        token = self.csrf(client)
        for _ in range(12):
            stage = self.staged(fx.epub2_full(), client=client)
            barrier = threading.Barrier(2)

            def save():
                barrier.wait()
                return client.json('/manage.php/import/save/' + stage['id'], self.save_form(stage, token))[0]

            def delete():
                barrier.wait()
                return client.json('/manage.php/import/delete', {'csrf_token': token, 'id': stage['id']})[0]

            with ThreadPoolExecutor(max_workers=2) as pool:
                saved, deleted = pool.submit(save), pool.submit(delete)
                saved, deleted = saved.result(), deleted.result()
            self.assertEqual(deleted, 200)
            self.assertIn(saved, (200, 404))
            self.assertEqual(client.json('/manage.php/import/status/' + stage['id'])[0], 404)
            self.assertEqual(self.stage_count(client), 0)
        listing = self.compose('exec', '-T', '-u', 'www-data', 'web', 'sh', '-c',
                               'ls -A /var/www/html/caches/import/a%d' % self.owned_admins[-1])
        self.assertEqual(listing.split(), ['.lock'])

    def test_web_restart_keeps_staging_usable(self):
        stage = self.staged(fx.epub3_full())
        self.compose('restart', 'web')
        deadline = time.time() + 90
        while True:
            try:
                status = self.admin.request('/manage.php/import/status/' + stage['id'])[0]
            except OSError:
                status = 0
            if status == 200 or time.time() > deadline:
                break
            time.sleep(2)
        self.assertEqual(status, 200)
        self.assertEqual(self.section(stage, 5)[0]['type'], 'image')
        self.assertEqual(self.staged(fx.minimal())['sections'][0]['include'], True)
        mode = self.compose('exec', '-T', '-u', 'www-data', 'web', 'stat', '-c', '%U %a', '/var/www/html/caches/import')
        self.assertEqual(mode.strip(), 'www-data 700')

    # ---- hostile or invalid archives --------------------------------------------

    def test_corrupt_and_non_zip_files(self):
        self.rejected(b'not a zip at all', 'invalid_zip')
        self.rejected(fx.epub3_full()[:-200], 'invalid_zip')
        self.rejected(fx.minimal(), 'extension', status=400, name='book.zip')
        self.rejected(fx.build([('META-INF/container.xml', fx.CONTAINER.format(opf='x.opf'))], mimetype=b'application/zip'), 'invalid_epub')

    def test_path_traversal_absolute_symlink_and_duplicates(self):
        self.rejected(fx.minimal(extra_files=[('../evil.txt', 'x')]), 'path_traversal')
        self.rejected(fx.minimal(extra_files=[('OEBPS/../../evil.txt', 'x')]), 'path_traversal')
        self.rejected(fx.minimal(extra_files=[('/etc/evil.txt', 'x')]), 'absolute_path')
        self.rejected(fx.minimal(body='<p><img src="../../../etc/passwd"/></p>'), 'path_traversal')
        self.rejected(fx.minimal(body='<p><img src="%2E%2E/%2E%2E/x.png"/></p>'), 'path_traversal')
        data = fx.build([('META-INF/container.xml', fx.CONTAINER.format(opf='OEBPS/content.opf'))], extra=fx.symlink_entry)
        self.rejected(data, 'symlink')
        # libzip's consistency check already refuses byte-identical duplicate names.
        dup = fx.minimal(extra_files=[('OEBPS/c1.xhtml', fx.xhtml('<p>second copy</p>'))])
        code, body = self.upload(dup)
        self.assertEqual(code, 422, body)
        self.assertIn(body['reason'], ('duplicate_path', 'invalid_zip'))
        self.rejected(fx.minimal(extra_files=[('OEBPS/C1.XHTML', 'x')]), 'duplicate_path')

    def test_missing_resources(self):
        self.rejected(fx.minimal(body='<p><img src="missing.png"/></p>'), 'missing_resource')
        manifest = '<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/><item id="c2" href="gone.xhtml" media-type="application/xhtml+xml"/>'
        opf = fx.opf3(manifest, '<itemref idref="c1"/><itemref idref="c2"/>')
        self.rejected(fx.minimal(opf_override=opf), 'missing_resource')

    def test_xxe_is_rejected(self):
        evil = ('<?xml version="1.0"?>\n<!DOCTYPE package [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>\n'
                '<package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'
                '<dc:title>&xxe;</dc:title></metadata><manifest><item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>'
                '</manifest><spine><itemref idref="c1"/></spine></package>')
        self.rejected(fx.minimal(opf_override=evil), 'xxe')
        body = fx.xhtml('<p>&xxe;</p>').replace('<!DOCTYPE html>', '<!DOCTYPE html [<!ENTITY xxe SYSTEM "http://127.0.0.1/">]>')
        data = fx.build([('META-INF/container.xml', fx.CONTAINER.format(opf='OEBPS/content.opf')),
                         ('OEBPS/content.opf', fx.opf3('<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>', '<itemref idref="c1"/>')),
                         ('OEBPS/c1.xhtml', body)])
        self.rejected(data, 'xxe')
        bomb = fx.xhtml('<p>&a;</p>').replace('<!DOCTYPE html>', '<!DOCTYPE html [<!ENTITY a "aaaaaaaa">]>')
        data = fx.build([('META-INF/container.xml', fx.CONTAINER.format(opf='c.opf')),
                         ('c.opf', fx.opf3('<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>', '<itemref idref="c1"/>')),
                         ('c1.xhtml', bomb)])
        self.rejected(data, 'xxe')

    def test_scripts_unsafe_html_and_remote_resources(self):
        self.rejected(fx.minimal(body='<p>x</p><script>alert(1)</script>'), 'active_content')
        self.rejected(fx.minimal(body='<iframe src="c1.xhtml"></iframe>'), 'active_content')
        self.rejected(fx.minimal(body='<p onclick="alert(1)">x</p>'), 'unsafe_html')
        self.rejected(fx.minimal(body='<p><a href="javascript:alert(1)">x</a></p>'), 'unsafe_html')
        self.rejected(fx.minimal(body='<p><img src="http://127.0.0.1/x.png"/></p>'), 'remote_resource')
        self.rejected(fx.minimal(body='<p><img src="//example.invalid/x.png"/></p>'), 'remote_resource')
        self.rejected(fx.minimal(manifest_extra='<item id="r" href="https://example.invalid/r.css" media-type="text/css"/>'), 'remote_resource')

    def test_drm_and_encryption(self):
        enc = ('<encryption xmlns="urn:oasis:names:tc:opendocument:xmlns:container" xmlns:enc="http://www.w3.org/2001/04/xmlenc#">'
               '<enc:EncryptedData><enc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>'
               '<enc:CipherData><enc:CipherReference URI="OEBPS/c1.xhtml"/></enc:CipherData></enc:EncryptedData></encryption>')
        self.rejected(fx.minimal(extra_files=[('META-INF/encryption.xml', enc)]), 'drm')
        self.rejected(fx.minimal(extra_files=[('META-INF/rights.xml', '<rights/>')]), 'drm')
        font = enc.replace('http://www.w3.org/2001/04/xmlenc#aes128-cbc', 'http://www.idpf.org/2008/embedding')
        stage = self.staged(fx.minimal(extra_files=[('META-INF/encryption.xml', font)]))
        self.assertTrue(any('字体' in w for w in stage['warnings']))

    def test_disguised_and_invalid_images(self):
        files = [('OEBPS/fake.png', b'<?php echo 1; ?>')]
        self.rejected(fx.minimal(body='<p><img src="fake.png"/></p>', extra_files=files), 'invalid_image')
        files = [('OEBPS/fake.jpg', fx.png()[:8] + b'garbage')]
        self.rejected(fx.minimal(body='<p><img src="fake.jpg"/></p>', extra_files=files), 'invalid_image')
        files = [('OEBPS/poly.png', fx.png() + b'<?php system($_GET[1]); ?>')]
        self.rejected(fx.minimal(body='<p><img src="poly.png"/></p>', extra_files=files), 'invalid_image')
        # A real GIF with a .png name is accepted but served with its actual type.
        stage = self.staged(fx.minimal(body='<p>文字</p><p><img src="actually.png"/></p>', extra_files=[('OEBPS/actually.png', fx.gif())]))
        url = self.section(stage, 0)[1]['url']
        status, headers, _ = self.admin.request(url[url.index('/manage.php'):])
        self.assertEqual((status, headers['Content-Type']), (200, 'image/gif'))

    def staging_entries(self):
        path = '/var/www/html/caches/import/a%d' % self.admin_id
        out = self.compose('exec', '-T', '-u', 'www-data', 'web', 'sh', '-c', 'ls -A "$1" 2>/dev/null || true', 'sh', path)
        return sorted(out.split())

    def test_paragraph_flood_is_rejected_before_memory_exhaustion(self):
        flood = fx.minimal(body='<p>x</p>' * 600000)
        started = time.time()
        body = self.rejected(flood, 'too_complex')
        self.assertLess(time.time() - started, 30)
        self.assertIn('请拆分章节', body['msg'])
        self.assertLess(len(json.dumps(body)), 1024)
        self.assertEqual([e for e in self.staging_entries() if e != '.lock'], [])
        # A flood of lines inside one <pre> hits the per-section block budget instead.
        self.rejected(fx.minimal(body='<pre>' + 'x\n' * 30000 + '</pre>'), 'too_complex')
        self.assertEqual([e for e in self.staging_entries() if e != '.lock'], [])
        stage = self.staged(fx.minimal())
        self.assertEqual(stage['sections'][0]['paragraphs'], 1)
        status, _, _ = self.admin.request('/manage.php/import/index', headers={'Accept': 'text/html'})
        self.assertEqual(status, 200)

    def test_truncated_and_corrupt_raster_images(self):
        exact = fx.minimal(body='<p>Text.</p><img src="p.png"/>', extra_files=[('OEBPS/p.png', fx.png()[:33])],
                           manifest_extra='<item id="p" href="p.png" media-type="image/png"/>')
        self.rejected(exact, 'invalid_image')
        bad_crc = bytearray(fx.png())
        bad_crc[43] ^= 0x01
        rows = b'\x00' + b'\x10' * 6
        bomb = zlib.compress(bytes(64 * MIB), 9)
        cases = [
            ('crc.png', bytes(bad_crc)),
            ('zlib.png', fx.png_with(2, 2, b'not a zlib stream')),
            ('short.png', fx.png_with(2, 2, zlib.compress(rows))),
            ('long.png', fx.png_with(2, 2, zlib.compress(rows * 3))),
            ('huge.png', fx.png_with(20000, 2000, zlib.compress(b'\x00' * 64))),
            ('bomb.png', fx.png_with(4000, 4000, bomb)),
            ('noeoi.jpg', fx.jpeg()[:-2]),
            ('half.jpg', fx.jpeg()[:len(fx.jpeg()) // 2]),
            ('nodata.jpg', fx.jpeg()[:fx.jpeg().index(b'\xff\xda') + 14] + b'\xff\xd9'),
            ('notrailer.gif', fx.gif()[:-1]),
            ('cut.gif', fx.gif()[:-5]),
        ]
        for name, payload in cases:
            with self.subTest(name):
                started = time.time()
                self.rejected(fx.minimal(body='<p>Text.</p><img src="%s"/>' % name, extra_files=[('OEBPS/' + name, payload)]), 'invalid_image')
                self.assertLess(time.time() - started, 30)
        self.assertEqual([e for e in self.staging_entries() if e != '.lock'], [])

    def test_valid_png_jpeg_gif_are_staged_and_served(self):
        images = [('a.png', fx.png(3, 5), 'image/png'), ('b.jpg', fx.jpeg(), 'image/jpeg'), ('c.gif', fx.gif(), 'image/gif')]
        body = '<p>Text.</p>' + ''.join('<img src="%s"/>' % name for name, _, _ in images)
        stage = self.staged(fx.minimal(body=body, extra_files=[('OEBPS/' + n, d) for n, d, _ in images]))
        blocks = [b for b in self.section(stage, 0) if b['type'] == 'image']
        self.assertEqual(len(blocks), 3)
        for block, (_, data, mime) in zip(blocks, images):
            status, headers, served = self.admin.request(block['url'][block['url'].index('/manage.php'):])
            self.assertEqual((status, headers['Content-Type'], served), (200, mime, data))

    def test_undecodable_audit_probes_are_rejected(self):
        mimes = {'png': 'image/png', 'jpg': 'image/jpeg', 'gif': 'image/gif', 'webp': 'image/webp'}
        for name, payload in fx.AUDIT_PROBES.items():
            if name.startswith('valid-'):
                continue
            with self.subTest(name):
                epub = fx.minimal(body='<p>Probe.</p><img src="%s"/>' % name, extra_files=[('OEBPS/' + name, payload)],
                                  manifest_extra='<item id="p" href="%s" media-type="%s"/>' % (name, mimes[name.rsplit('.', 1)[1]]))
                self.rejected(epub, 'invalid_image')
        self.assertEqual([e for e in self.staging_entries() if e != '.lock'], [])

    def test_webp_declared_sizes_must_match_bitstreams(self):
        probes = {'canvas+frames': fx.webp_misdeclared(), 'frames': fx.webp_misdeclared(canvas=False),
                  'canvas': fx.webp_misdeclared(frames=False), 'still-canvas': fx.webp_still(canvas=(1, 1))}
        for name, payload in probes.items():
            with self.subTest(name):
                epub = fx.minimal(body='<p>Synthetic.</p><img src="p.webp"/>', extra_files=[('OEBPS/p.webp', payload)],
                                  manifest_extra='<item id="p" href="p.webp" media-type="image/webp"/>')
                self.rejected(epub, 'invalid_image')
        self.assertEqual([e for e in self.staging_entries() if e != '.lock'], [])

    def test_animated_and_alpha_images_are_decoded_and_served(self):
        images = [('valid-animated.webp', fx.AUDIT_PROBES['valid-animated.webp'], 'image/webp'),
                  ('still.webp', fx.webp_still(), 'image/webp'), ('still-x.webp', fx.webp_still(canvas=(2, 2)), 'image/webp'),
                  ('alpha.webp', fx.ALPHA_ANIM_WEBP, 'image/webp'), ('anim.gif', fx.ANIM_GIF, 'image/gif')]
        body = '<p>Text.</p>' + ''.join('<img src="%s"/>' % name for name, _, _ in images)
        stage = self.staged(fx.minimal(body=body, extra_files=[('OEBPS/' + n, d) for n, d, _ in images]))
        blocks = [b for b in self.section(stage, 0) if b['type'] == 'image']
        self.assertEqual(len(blocks), len(images))
        for block, (_, data, mime) in zip(blocks, images):
            status, headers, served = self.admin.request(block['url'][block['url'].index('/manage.php'):])
            self.assertEqual((status, headers['Content-Type'], served), (200, mime, data))

    def test_large_png_within_decode_budget(self):
        # 3000 x 3000 RGB8: 108 MB estimated decode memory, under max_decode_bytes; 4000 x 4000 (bomb.png) is above it.
        raw = (b'\x00' + b'\x80' * 9000) * 3000
        stage = self.staged(fx.minimal(body='<p>Big.</p><img src="big.png"/>', extra_files=[('OEBPS/big.png', fx.png_with(3000, 3000, zlib.compress(raw, 9)))]))
        self.assertEqual(sum(b['type'] == 'image' for b in self.section(stage, 0)), 1)

    def test_size_and_decompression_limits(self):
        status, body = self.upload(os.urandom(10 * MIB + 4096))
        self.assertEqual((status, body['reason']), (413, 'too_large'))
        big = fx.minimal(extra_files=[('OEBPS/big.bin', b'\0' * (10 * MIB + 1))])
        self.rejected(big, 'entry_too_large')
        many = fx.minimal(extra_files=[('OEBPS/pad%d.bin' % i, b'\0' * (9 * MIB)) for i in range(12)])
        self.assertLess(len(many), 10 * MIB)
        self.rejected(many, 'total_too_large')
        entries = fx.minimal(extra_files=[('OEBPS/e%d.txt' % i, '') for i in range(2000)])
        self.rejected(entries, 'too_many_entries')
        liar = fx.lying_size(fx.minimal(body='<p>' + 'x' * 5000 + '</p>'), 'OEBPS/c1.xhtml')
        code, body = self.upload(liar)
        self.assertEqual(code, 422, body)
        self.assertIn(body['reason'], ('invalid_zip', 'entry_too_large'))


if __name__ == '__main__':
    unittest.main()
