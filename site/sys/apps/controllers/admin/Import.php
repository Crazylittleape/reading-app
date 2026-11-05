<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/Import_staging.php';
/**
 * EPUB import, batch 1: authenticated upload, safe parsing and an editable
 * private preview. Nothing here writes mc_book or chapter rows and nothing is
 * copied into the public attachment tree.
 */
class Import extends Mccms_Controller {
	private $raw_post = null;
	private $operator;
	private $limits;
	private $staging;
	private static $pages = array('index', 'preview');

	function __construct(){
		parent::__construct();
		// Every endpoint re-validates the encrypted admin cookie against an active account.
		$method = strtolower($this->router->fetch_method());
		$this->operator = $this->admin->operator(!in_array($method, self::$pages, true), 'import/index');
		$this->limits = Epub_parser::load_limits();
		$this->staging = new Import_staging($this->limits);
		header('Cache-Control: no-store, private');
		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: same-origin');
	}

	public function index(){
		$this->staging->purge_expired();
		$data['stages'] = $this->staging->listing($this->operator->id);
		$data['csrf'] = $this->csrf_token();
		$data['limits'] = $this->limits;
		$this->load->view('import/index.tpl', $data);
	}

	public function upload(){
		$this->require_post();
		$length = isset($_SERVER['CONTENT_LENGTH']) ? intval($_SERVER['CONTENT_LENGTH']) : 0;
		if ($length > $this->limits['max_upload_bytes'] + 65536) $this->json(array('code' => -1, 'reason' => 'too_large', 'msg' => '上传文件超过 10 MiB 限制'), 413);
		$this->require_csrf();
		$file = isset($_FILES['epub']) && is_array($_FILES['epub']) ? $_FILES['epub'] : null;
		if (!$file || is_array($file['error'])) $this->json(array('code' => -1, 'reason' => 'no_file', 'msg' => '请选择 EPUB 文件'), 400);
		if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) $this->json(array('code' => -1, 'reason' => 'too_large', 'msg' => '上传文件超过 10 MiB 限制'), 413);
		if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) $this->json(array('code' => -1, 'reason' => 'no_file', 'msg' => '文件上传失败，请重试'), 400);
		if (strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'epub') $this->json(array('code' => -1, 'reason' => 'extension', 'msg' => '只支持 .epub 文件'), 400);
		try {
			$meta = $this->staging->create($this->operator->id, $file['tmp_name'], $file['name']);
		} catch (Epub_import_error $e) {
			$this->json(array('code' => -1, 'reason' => $e->reason, 'msg' => $e->getMessage()), $e->reason === 'server' ? 500 : ($e->reason === 'quota' ? 409 : ($e->reason === 'too_large' ? 413 : 422)));
		} catch (Throwable $e) {
			log_message('error', 'EPUB import failed: '.get_class($e));
			$this->json(array('code' => -1, 'reason' => 'invalid_epub', 'msg' => 'EPUB 无法解析'), 422);
		} finally {
			@unlink($file['tmp_name']);
		}
		$this->json(array('code' => 1, 'msg' => '解析完成，请检查预览', 'id' => $meta['id'], 'url' => links('import', 'preview', $meta['id'])));
	}

	public function preview($id = ''){
		$meta = $this->stage($id, false);
		$data['id'] = $meta['id'];
		$data['csrf'] = $this->csrf_token();
		$data['class'] = $this->categories();
		$this->load->view('import/preview.tpl', $data);
	}

	public function status($id = ''){
		$meta = $this->stage($id);
		$book = $meta['book'];
		$sections = array();
		foreach ($book['sections'] as $i => $s) {
			$edit = $meta['edit']['sections'][$i];
			$sections[] = array('n' => $s['n'], 'title' => $edit['title'], 'auto_title' => $s['auto_title'], 'title_source' => $s['title_source'],
				'include' => $edit['include'], 'kind' => $s['kind'], 'note' => $s['note'], 'source' => $s['source'], 'linear' => $s['linear'],
				'paragraphs' => $s['paragraphs'], 'images' => $s['images'], 'text_num' => $s['text_num']);
		}
		$this->json(array('code' => 1, 'stage' => array(
			'id' => $meta['id'], 'file' => $meta['file'], 'bytes' => $meta['bytes'], 'sha256' => $meta['sha256'],
			'created' => $meta['created'], 'expires' => $meta['expires'], 'saved' => $meta['edit']['saved'],
			'version' => $book['version'], 'language' => $book['language'], 'identifier' => $book['identifier'],
			'source_title' => $book['title'], 'source_author' => $book['author'],
			'title' => $meta['edit']['title'], 'author' => $meta['edit']['author'], 'cid' => $meta['edit']['cid'],
			'cover' => $book['cover'] === null ? '' : $this->image_url($meta['id'], $book['cover']),
			'images' => count($book['images']), 'text_num' => $book['text_num'], 'warnings' => $book['warnings'],
			'sections' => $sections,
		)));
	}

	public function section($id = '', $n = ''){
		$meta = $this->stage($id);
		if (!ctype_digit((string)$n) || !isset($meta['book']['sections'][intval($n)])) $this->json(array('code' => -1, 'msg' => '分节不存在'), 404);
		$blocks = $this->staging->section($meta, intval($n));
		if ($blocks === null) $this->json(array('code' => -1, 'msg' => '分节不存在'), 404);
		$out = array();
		foreach ($blocks as $block) {
			if ($block['type'] === 'text') $out[] = array('type' => 'text', 'text' => $block['text']);
			else $out[] = array('type' => 'image', 'url' => $this->image_url($meta['id'], $block['image']), 'alt' => $block['alt']);
		}
		$this->json(array('code' => 1, 'n' => intval($n), 'blocks' => $out));
	}

	public function image($id = '', $n = ''){
		$meta = $this->stage($id);
		$image = ctype_digit((string)$n) ? $this->staging->image_file($meta, intval($n)) : null;
		if (!$image) $this->json(array('code' => -1, 'msg' => '图片不存在'), 404);
		header('Content-Type: '.$image['mime']);
		header('Content-Length: '.filesize($image['file']));
		header('Content-Disposition: inline; filename="image"');
		header("Content-Security-Policy: default-src 'none'; sandbox");
		header('Cross-Origin-Resource-Policy: same-origin');
		readfile($image['file']);
		exit;
	}

	public function save($id = ''){
		$this->require_post();
		// Only the urlencoded body can be re-read raw; multipart $_POST is already UTF-8-"cleaned" by CI_Input.
		if (!$this->urlencoded()) $this->json(array('code' => -1, 'reason' => 'content_type', 'msg' => '保存请求必须使用 application/x-www-form-urlencoded 表单'), 415);
		$this->require_csrf();
		$meta = $this->stage($id);
		$title = $this->text_field('title', '书名', $this->limits['max_title_chars']);
		$author = $this->text_field('author', '作者', $this->limits['max_author_chars']);
		$cid = intval($this->input->post('cid'));
		if ($title === '') $this->json(array('code' => -1, 'msg' => '书名不能为空'), 422);
		if ($author === '') $this->json(array('code' => -1, 'msg' => '作者不能为空'), 422);
		if ($cid <= 0 || !$this->mcdb->get_row('book_class', 'id', array('id' => $cid))) $this->json(array('code' => -1, 'msg' => '请选择有效的分类'), 422);
		// One JSON field keeps the request far below max_input_vars for any spine size.
		$count = count($meta['book']['sections']);
		// Read the raw field: Mc_Input passes POST values through str_encode(), which HTML-encodes them and would corrupt the JSON.
		$raw = $this->raw_post('sections');
		if (!is_string($raw) || strlen($raw) > $count * 1024 + 1024) $this->json(array('code' => -1, 'msg' => '分节数据不完整，请刷新后重试'), 422);
		$items = json_decode($raw, true, 4);
		if (!is_array($items) || count($items) !== $count || array_keys($items) !== range(0, $count - 1)) $this->json(array('code' => -1, 'msg' => '分节数据不完整，请刷新后重试'), 422);
		$sections = array();
		$kept = 0;
		foreach ($meta['book']['sections'] as $i => $s) {
			$item = $items[$i];
			if (!is_array($item) || !isset($item['title'], $item['include']) || !is_string($item['title']) || !is_bool($item['include'])) {
				$this->json(array('code' => -1, 'msg' => '分节数据格式错误，请刷新后重试'), 422);
			}
			$value = Epub_parser::clean($item['title']);
			if ($value === '') $this->json(array('code' => -1, 'msg' => '第 '.($i + 1).' 个分节标题不能为空'), 422);
			if (mb_strlen($value, 'UTF-8') > 128) $this->json(array('code' => -1, 'msg' => '第 '.($i + 1).' 个分节标题过长'), 422);
			$sections[] = array('title' => $value, 'include' => $item['include']);
			if ($item['include'] && ($s['paragraphs'] > 0 || $s['images'] > 0)) $kept++;
		}
		if ($kept === 0) $this->json(array('code' => -1, 'msg' => '至少保留一个有内容的分节'), 422);
		$edit = array('title' => $title, 'author' => $author, 'cid' => $cid, 'sections' => $sections, 'saved' => time());
		if (!$this->staging->save($meta, $edit)) $this->json(array('code' => -1, 'msg' => '预览不存在或已过期'), 404);
		$this->json(array('code' => 1, 'msg' => '预览修改已保存（尚未导入书库）'));
	}

	public function delete(){
		$this->require_post();
		$this->require_csrf();
		$id = (string)$this->input->post('id');
		if (!$this->staging->remove($this->operator->id, $id)) $this->json(array('code' => -1, 'msg' => '预览不存在或已过期'), 404);
		$this->json(array('code' => 1, 'msg' => '预览已删除'));
	}

	public function cleanup(){
		$this->require_post();
		$this->require_csrf();
		$removed = $this->staging->purge_expired();
		$this->json(array('code' => 1, 'removed' => $removed, 'msg' => '已清理 '.$removed.' 个过期预览'));
	}

	// ---- helpers -------------------------------------------------------------

	private function stage($id, $json = true){
		$meta = $this->staging->load($this->operator->id, (string)$id);
		if ($meta) return $meta;
		if ($json) $this->json(array('code' => -1, 'msg' => '预览不存在或已过期'), 404);
		http_response_code(404);
		header('Content-Type: text/html; charset=utf-8');
		die('预览不存在或已过期');
	}

	private function categories(){
		$rows = $this->mcdb->get_select('book_class', 'id,name,fid', '', 'xid ASC', 500);
		$tree = array();
		foreach ($rows as $row) if (intval($row['fid']) === 0) $tree[$row['id']] = array('id' => $row['id'], 'name' => $row['name'], 'children' => array());
		foreach ($rows as $row) if (intval($row['fid']) > 0 && isset($tree[$row['fid']])) $tree[$row['fid']]['children'][] = $row;
		return array_values($tree);
	}

	private function text_field($name, $label, $max){
		// Raw, like `sections`: Mc_Input's str_encode() would store quotes, <, > and & HTML-encoded. Pages escape on output.
		$value = $this->raw_post($name);
		if ($value === null) $value = '';
		if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) $this->json(array('code' => -1, 'msg' => $label.'格式无效'), 422);
		$value = Epub_parser::clean($value);
		if (mb_strlen($value, 'UTF-8') > $max) $this->json(array('code' => -1, 'msg' => $label.'不能超过 '.$max.' 个字符'), 422);
		return $value;
	}

	/**
	 * Form fields parsed from the urlencoded request body. CI_Input has already replaced $_POST values with
	 * UTF-8-"cleaned" copies (invalid bytes silently dropped), which would hide malformed input from validation.
	 */
	private function raw_post($name){
		if ($this->raw_post === null) {
			$this->raw_post = array();
			if ($this->urlencoded()) parse_str((string)file_get_contents('php://input'), $this->raw_post);
		}
		return isset($this->raw_post[$name]) ? $this->raw_post[$name] : null;
	}

	private function urlencoded(){
		$type = isset($_SERVER['CONTENT_TYPE']) ? explode(';', (string)$_SERVER['CONTENT_TYPE'], 2)[0] : '';
		return strtolower(trim($type)) === 'application/x-www-form-urlencoded';
	}

	private function image_url($id, $n){
		return links('import', 'image', $id).'/'.intval($n);
	}

	private function csrf_key($issued){
		return hash_hmac('sha256', 'epub-import|'.$this->operator->id.'|'.$this->operator->pass.'|'.$issued, Admin_Code.'|'.Web_Rkpass);
	}

	private function csrf_token(){
		$issued = time();
		return $issued.'.'.$this->csrf_key($issued);
	}

	private function require_csrf(){
		$token = $this->input->post('csrf_token');
		if (!is_string($token) || $token === '') $token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string)$_SERVER['HTTP_X_CSRF_TOKEN'] : '';
		$parts = explode('.', $token, 2);
		$valid = count($parts) === 2 && ctype_digit($parts[0]) && intval($parts[0]) <= time() + 60
			&& intval($parts[0]) >= time() - $this->limits['csrf_ttl'] && hash_equals($this->csrf_key($parts[0]), $parts[1]);
		if (!$valid) $this->json(array('code' => -1, 'reason' => 'csrf', 'msg' => '安全令牌无效或已过期，请刷新页面后重试'), 403);
	}

	private function require_post(){
		if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
			header('Allow: POST');
			$this->json(array('code' => -1, 'reason' => 'method', 'msg' => '只允许 POST 请求'), 405);
		}
	}

	private function json($data, $status = 200){
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
		exit;
	}
}
