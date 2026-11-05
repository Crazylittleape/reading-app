<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Bounded, non-networked EPUB 2 / EPUB 3 reader for the admin import preview.
 *
 * The archive is never extracted by its own file names. Entries are read
 * through ZipArchive streams with byte budgets, XML is parsed without DTD,
 * entity substitution or network access, and only plain text paragraphs and
 * validated raster images are written to the caller's private work directory.
 */
class Epub_import_error extends Exception {
	public $reason;
	public function __construct($reason, $message) {
		parent::__construct($message);
		$this->reason = $reason;
	}
}

class Epub_parser {
	const NS_OPF = 'http://www.idpf.org/2007/opf';
	const NS_DC = 'http://purl.org/dc/elements/1.1/';
	const NS_OPS = 'http://www.idpf.org/2007/ops';

	private $limits;
	private $zip;
	private $entries = array();    // normalized path => array(index, size)
	private $read_total = 0;
	private $workdir;
	private $images = array();     // archive path => image record
	private $warnings = array();
	private $block_total = 0;

	private static $font_obfuscation = array('http://www.idpf.org/2008/embedding', 'http://ns.adobe.com/pdf/enc#RC');
	private static $active = array('script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'base');
	private static $skip = array('head', 'style', 'title', 'noscript', 'template', 'audio', 'video', 'canvas', 'math');
	private static $blocks = array('p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'ul', 'ol', 'dl', 'dt', 'dd',
		'blockquote', 'pre', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption', 'section', 'article',
		'header', 'footer', 'aside', 'nav', 'figure', 'figcaption', 'hr', 'address', 'main', 'body', 'center', 'details', 'summary');
	private static $raster = array(
		IMAGETYPE_JPEG => array('image/jpeg', 'jpg'),
		IMAGETYPE_PNG => array('image/png', 'png'),
		IMAGETYPE_GIF => array('image/gif', 'gif'),
		IMAGETYPE_WEBP => array('image/webp', 'webp'),
	);

	public function __construct($limits = array()) {
		if (empty($limits)) $limits = self::load_limits();
		$this->limits = $limits;
	}

	public static function load_limits() {
		$config = array();
		include APPPATH.'config/epub_import.php';
		return $config['epub_import'];
	}

	/**
	 * Parse $file and write sec/N.json and img/N.ext into $workdir.
	 * Returns the manifest array; throws Epub_import_error on any rejection.
	 */
	public function parse($file, $workdir) {
		if (!class_exists('ZipArchive')) throw new Epub_import_error('server', '服务器缺少 zip 扩展，无法解析 EPUB');
		$this->workdir = rtrim($workdir, '/').'/';
		$size = @filesize($file);
		if ($size === false || $size <= 0) throw new Epub_import_error('empty', '上传文件为空');
		if ($size > $this->limits['max_upload_bytes']) throw new Epub_import_error('too_large', '上传文件超过 '.self::mib($this->limits['max_upload_bytes']).' 限制');
		$fp = fopen($file, 'rb');
		$magic = $fp ? fread($fp, 4) : '';
		if ($fp) fclose($fp);
		if ($magic !== "PK\x03\x04") throw new Epub_import_error('invalid_zip', '文件不是有效的 EPUB（ZIP）压缩包');

		$previous = libxml_use_internal_errors(true);
		libxml_set_external_entity_loader(function () { return null; });
		$this->zip = new ZipArchive();
		$opened = $this->zip->open($file, ZipArchive::RDONLY | ZipArchive::CHECKCONS);
		if ($opened !== true) {
			libxml_use_internal_errors($previous);
			throw new Epub_import_error('invalid_zip', 'ZIP 结构损坏或不完整，无法读取');
		}
		try {
			return $this->parse_archive();
		} finally {
			$this->zip->close();
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
			libxml_set_external_entity_loader(null);
		}
	}

	private function parse_archive() {
		$this->index_entries();
		$this->check_mimetype();
		$this->check_drm();
		$opf_path = $this->rootfile();
		$opf = $this->load_xml($opf_path);
		$xp = new DOMXPath($opf);
		$xp->registerNamespace('opf', self::NS_OPF);
		$xp->registerNamespace('dc', self::NS_DC);
		$package = $opf->documentElement;
		if (!$package || $package->localName !== 'package') throw new Epub_import_error('invalid_epub', 'OPF 文件缺少 package 根元素');
		$version = trim($package->getAttribute('version'));
		$base = self::dirname($opf_path);

		$manifest = array();
		foreach ($xp->query('//*[local-name()="manifest"]/*[local-name()="item"]') as $item) {
			$id = $item->getAttribute('id');
			$href = $item->getAttribute('href');
			if ($id === '' || $href === '') continue;
			if (self::is_remote($href)) throw new Epub_import_error('remote_resource', 'OPF 清单引用了外部资源（'.self::short($href).'），不会下载');
			$path = $this->resolve($base, $href);
			$manifest[$id] = array(
				'id' => $id, 'path' => $path, 'type' => strtolower(trim($item->getAttribute('media-type'))),
				'props' => ' '.trim($item->getAttribute('properties')).' ',
			);
			if (!isset($this->entries[$path])) $this->warn('清单资源缺失，已忽略：'.self::short($path));
		}
		$meta = $this->metadata($xp, $manifest);

		$spine_node = $xp->query('//*[local-name()="spine"]')->item(0);
		$spine = array();
		if ($spine_node) {
			foreach ($xp->query('*[local-name()="itemref"]', $spine_node) as $ref) {
				$idref = $ref->getAttribute('idref');
				if (!isset($manifest[$idref])) throw new Epub_import_error('invalid_epub', 'spine 引用了清单中不存在的项目：'.self::short($idref));
				if (!isset($this->entries[$manifest[$idref]['path']])) throw new Epub_import_error('missing_resource', '阅读顺序中的文件不存在：'.self::short($manifest[$idref]['path']));
				$spine[] = array('item' => $manifest[$idref], 'linear' => strtolower($ref->getAttribute('linear')) !== 'no');
			}
		}
		if (empty($spine)) throw new Epub_import_error('no_spine', 'OPF 缺少有效的 spine 阅读顺序');
		if (count($spine) > $this->limits['max_sections']) throw new Epub_import_error('too_many_sections', '分节数量超过 '.$this->limits['max_sections']);

		// Navigation labels: EPUB 3 nav first, NCX as fallback. Both only name sections.
		$labels = array();
		$roles = array();
		$nav_path = '';
		foreach ($manifest as $item) {
			if (strpos($item['props'], ' nav ') !== false && isset($this->entries[$item['path']])) {
				$nav_path = $item['path'];
				$this->nav_labels($nav_path, $labels, $roles);
				break;
			}
		}
		$ncx_id = $spine_node ? $spine_node->getAttribute('toc') : '';
		$ncx = isset($manifest[$ncx_id]) ? $manifest[$ncx_id] : null;
		if (!$ncx) foreach ($manifest as $item) if ($item['type'] === 'application/x-dtbncx+xml') { $ncx = $item; break; }
		if ($ncx && isset($this->entries[$ncx['path']])) $this->ncx_labels($ncx['path'], $labels);
		foreach ($xp->query('//*[local-name()="guide"]/*[local-name()="reference"]') as $ref) {
			$href = $ref->getAttribute('href');
			if ($href === '' || self::is_remote($href)) continue;
			$type = strtolower(trim($ref->getAttribute('type')));
			$target = $this->resolve($base, $href);
			if (!isset($roles[$target])) $roles[$target] = $type;
		}

		$cover_index = null;
		if ($meta['cover_path'] !== '') {
			$image = $this->image($meta['cover_path'], $meta['cover_type'], true);
			if ($image) $cover_index = $image['n'];
		}

		$sections = array();
		$book_text = 0;
		foreach ($spine as $i => $entry) {
			$item = $entry['item'];
			$path = $item['path'];
			$n = count($sections);
			$section = array('n' => $n, 'source' => $path, 'linear' => $entry['linear'], 'title' => '', 'title_source' => '',
				'kind' => 'text', 'include' => true, 'note' => '', 'paragraphs' => 0, 'images' => 0, 'text_num' => 0);
			if (!in_array($item['type'], array('application/xhtml+xml', 'text/html', 'application/x-dtbook+xml'), true)) {
				$section['kind'] = 'unsupported';
				$section['include'] = false;
				$section['note'] = '不支持的 spine 类型（'.$item['type'].'），已排除';
				$blocks = array();
			} else {
				$doc = $this->extract_xhtml($path);
				$blocks = $doc['blocks'];
				foreach ($blocks as $block) {
					if ($block['type'] === 'text') { $section['paragraphs']++; $section['text_num'] += mb_strlen($block['text'], 'UTF-8'); }
					else $section['images']++;
				}
				if (isset($labels[$path])) { $section['title'] = $labels[$path]['label']; $section['title_source'] = $labels[$path]['from']; }
				elseif ($doc['heading'] !== '') { $section['title'] = $doc['heading']; $section['title_source'] = 'heading'; }
				$role = isset($roles[$path]) ? $roles[$path] : '';
				$only_cover = $section['paragraphs'] === 0 && $section['images'] === 1;
				if ($path === $nav_path || $role === 'toc') {
					$section['kind'] = 'nav';
					$section['include'] = false;
					$section['note'] = '原书目录页；阅读目录由分节标题生成，默认排除，可手动保留';
				} elseif ($role === 'cover' || $role === 'cover-page' || (count($spine) > 1 && $n === 0 && $only_cover && ($cover_index === null || $blocks[0]['image'] === $cover_index))) {
					$section['kind'] = 'cover';
					$section['include'] = false;
					$section['note'] = '封面页，图片已作为书籍封面，默认排除，可手动保留';
					if ($cover_index === null && $only_cover) $cover_index = $blocks[0]['image'];
				} elseif ($section['paragraphs'] === 0 && $section['images'] > 0) {
					$section['kind'] = 'image';
					$section['note'] = '图片页，保留';
				} elseif ($section['paragraphs'] === 0) {
					$section['kind'] = 'empty';
					$section['include'] = false;
					$section['note'] = '无文字和图片，排除';
				} elseif (!$entry['linear']) {
					$section['note'] = '非线性内容（linear="no"），按 spine 位置保留';
				}
			}
			if ($section['title'] === '') {
				$section['title'] = '未命名小节 '.($n + 1);
				$section['title_source'] = 'fallback';
			}
			$section['auto_title'] = $section['title'];
			$this->write_json($this->workdir.'sec/'.$n.'.json', $blocks);
			if ($section['include']) $book_text += $section['text_num'];
			$sections[] = $section;
		}
		$readable = 0;
		$content = 0;
		foreach ($sections as $s) {
			if ($s['paragraphs'] > 0 || $s['images'] > 0) {
				$content++;
				if ($s['include']) $readable++;
			}
		}
		if ($content === 0) throw new Epub_import_error('no_content', '没有可阅读的正文或图片分节');
		if ($readable === 0) {
			// Only cover/navigation pages carry content: keep them rather than discarding the book body.
			foreach ($sections as $i => $s) {
				if ($s['paragraphs'] > 0 || $s['images'] > 0) {
					$sections[$i]['include'] = true;
					$sections[$i]['note'] = '全书仅此类页面含内容，默认保留';
					$book_text += $s['text_num'];
				}
			}
			$this->warn('除封面/目录页外没有其他内容，已默认保留这些页面');
		}

		$images = array();
		foreach ($this->images as $img) $images[$img['n']] = array('n' => $img['n'], 'mime' => $img['mime'], 'ext' => $img['ext'],
			'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'], 'source' => $img['source']);
		ksort($images);
		return array(
			'version' => $version,
			'title' => $meta['title'],
			'author' => $meta['author'],
			'language' => $meta['language'],
			'identifier' => $meta['identifier'],
			'cover' => $cover_index,
			'sections' => $sections,
			'images' => array_values($images),
			'text_num' => $book_text,
			'warnings' => array_values(array_unique($this->warnings)),
			'read_bytes' => $this->read_total,
		);
	}

	// ---- archive -------------------------------------------------------------

	private function index_entries() {
		$count = $this->zip->numFiles;
		if ($count < 1) throw new Epub_import_error('invalid_zip', 'ZIP 压缩包为空');
		if ($count > $this->limits['max_entries']) throw new Epub_import_error('too_many_entries', 'ZIP 条目超过 '.$this->limits['max_entries'].' 个');
		$declared = 0;
		$folded = array();
		for ($i = 0; $i < $count; $i++) {
			$stat = $this->zip->statIndex($i, ZipArchive::FL_UNCHANGED);
			if ($stat === false) throw new Epub_import_error('invalid_zip', 'ZIP 目录项无法读取');
			$name = $stat['name'];
			if (!empty($stat['encryption_method'])) throw new Epub_import_error('drm', 'EPUB 含加密条目，不支持受保护或加密的电子书');
			if ($this->zip->getExternalAttributesIndex($i, $opsys, $attr, ZipArchive::FL_UNCHANGED)
				&& $opsys === ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
				throw new Epub_import_error('symlink', 'ZIP 含符号链接条目，已拒绝');
			}
			$path = self::entry_name($name);
			$is_dir = substr($name, -1) === '/';
			$key = mb_strtolower($path, 'UTF-8').($is_dir ? '/' : '');
			if (isset($folded[$key])) throw new Epub_import_error('duplicate_path', 'ZIP 含重复的规范化路径：'.self::short($path));
			$folded[$key] = true;
			if ($is_dir) continue;
			if ($stat['size'] > $this->limits['max_entry_bytes']) throw new Epub_import_error('entry_too_large', '单个条目超过 '.self::mib($this->limits['max_entry_bytes']).'：'.self::short($path));
			$declared += $stat['size'];
			if ($declared > $this->limits['max_total_bytes']) throw new Epub_import_error('total_too_large', '解压后总大小超过 '.self::mib($this->limits['max_total_bytes']));
			$this->entries[$path] = array('index' => $i, 'size' => (int)$stat['size'], 'name' => $name);
		}
	}

	/** Validate one stored ZIP name; traversal, absolute and ambiguous names are fatal. */
	public static function entry_name($name) {
		if ($name === '' || !mb_check_encoding($name, 'UTF-8') || preg_match('/[\x00-\x1f\x7f\\\\]/', $name)) {
			throw new Epub_import_error('invalid_path', 'ZIP 条目名称无效');
		}
		if ($name[0] === '/' || preg_match('#^[A-Za-z]:#', $name)) throw new Epub_import_error('absolute_path', 'ZIP 含绝对路径条目，已拒绝');
		$trimmed = substr($name, -1) === '/' ? substr($name, 0, -1) : $name;
		foreach (explode('/', $trimmed) as $part) {
			if ($part === '..') throw new Epub_import_error('path_traversal', 'ZIP 含路径穿越条目，已拒绝');
			if ($part === '' || $part === '.') throw new Epub_import_error('invalid_path', 'ZIP 条目路径不规范：'.self::short($name));
		}
		return $trimmed;
	}

	/** Read an entry with actual-byte budgets independent of declared sizes. */
	private function read($path, $limit = 0) {
		if (!isset($this->entries[$path])) throw new Epub_import_error('missing_resource', '引用的文件不存在：'.self::short($path));
		$entry = $this->entries[$path];
		$cap = $this->limits['max_entry_bytes'];
		if ($limit > 0) $cap = min($cap, $limit);
		if ($entry['size'] > $cap) throw new Epub_import_error('entry_too_large', '文件超过允许大小：'.self::short($path));
		$stream = $this->zip->getStream($entry['name']);
		if (!$stream) throw new Epub_import_error('invalid_zip', '无法读取压缩条目：'.self::short($path));
		$data = '';
		$length = 0;
		while (!feof($stream)) {
			$chunk = fread($stream, 65536);
			if ($chunk === false) break;
			$length += strlen($chunk);
			$this->read_total += strlen($chunk);
			if ($length > $cap || $length > $entry['size']) { fclose($stream); throw new Epub_import_error('entry_too_large', '压缩条目实际解压大小超出声明或限制：'.self::short($path)); }
			if ($this->read_total > $this->limits['max_total_bytes']) { fclose($stream); throw new Epub_import_error('total_too_large', '实际解压总量超过 '.self::mib($this->limits['max_total_bytes'])); }
			if ($chunk === '') break;
			$data .= $chunk;
		}
		fclose($stream);
		if ($length !== $entry['size']) throw new Epub_import_error('invalid_zip', '压缩条目损坏或大小与声明不符：'.self::short($path));
		return $data;
	}

	private function check_mimetype() {
		if (!isset($this->entries['mimetype'])) throw new Epub_import_error('invalid_epub', '缺少 mimetype 文件，不是有效的 EPUB');
		if (trim($this->read('mimetype', 256)) !== 'application/epub+zip') throw new Epub_import_error('invalid_epub', 'mimetype 不是 application/epub+zip');
	}

	private function check_drm() {
		foreach (array('META-INF/rights.xml', 'META-INF/sinf.xml') as $marker) {
			if (isset($this->entries[$marker])) throw new Epub_import_error('drm', '检测到 DRM 版权保护标记，不支持导入，也不会尝试解除保护');
		}
		if (!isset($this->entries['META-INF/encryption.xml'])) return;
		$doc = $this->load_xml('META-INF/encryption.xml');
		$xp = new DOMXPath($doc);
		$encrypted = $xp->query('//*[local-name()="EncryptedData"]');
		foreach ($encrypted as $node) {
			$method = $xp->query('.//*[local-name()="EncryptionMethod"]', $node)->item(0);
			$algorithm = $method ? $method->getAttribute('Algorithm') : '';
			if (!in_array($algorithm, self::$font_obfuscation, true)) {
				throw new Epub_import_error('drm', '检测到加密内容（encryption.xml），不支持受保护的 EPUB');
			}
		}
		if ($encrypted->length > 0) $this->warn('字体混淆仅作用于嵌入字体，字体不会导入');
	}

	private function rootfile() {
		$doc = $this->load_xml('META-INF/container.xml');
		$xp = new DOMXPath($doc);
		foreach ($xp->query('//*[local-name()="rootfile"]') as $root) {
			$type = $root->getAttribute('media-type');
			if ($type !== '' && $type !== 'application/oebps-package+xml') continue;
			$full = $root->getAttribute('full-path');
			if ($full === '' || self::is_remote($full)) break;
			$path = isset($this->entries[$full]) ? $full : $this->resolve('', $full);
			if (!isset($this->entries[$path])) throw new Epub_import_error('missing_resource', 'container.xml 指向的 OPF 不存在');
			return $path;
		}
		throw new Epub_import_error('invalid_epub', 'container.xml 未声明 OPF 包文件');
	}

	// ---- XML ---------------------------------------------------------------------

	/** Reject DTD internal subsets/entities before libxml sees them; never load DTDs. */
	private function xml_source($path, $limit) {
		$data = $this->read($path, $limit);
		if (substr($data, 0, 2) === "\xFF\xFE" || substr($data, 0, 2) === "\xFE\xFF") {
			$data = mb_convert_encoding(substr($data, 2), 'UTF-8', $data[0] === "\xFF" ? 'UTF-16LE' : 'UTF-16BE');
			$data = preg_replace('/^(<\?xml[^>]*?)\sencoding=["\'][^"\']*["\']/', '$1', $data);
		}
		if (substr($data, 0, 3) === "\xEF\xBB\xBF") $data = substr($data, 3);
		// Bound node/array growth before XMLReader or DOMDocument allocates anything.
		if (substr_count($data, '<') > $this->limits['max_xml_tags']) {
			throw new Epub_import_error('too_complex', '文档标签超过 '.$this->limits['max_xml_tags'].' 个，结构过于复杂，请拆分章节后再导入：'.self::short($path));
		}
		if (preg_match('/<!ENTITY/i', $data) || preg_match('/<!DOCTYPE[^>\[]*\[/i', $data)) {
			throw new Epub_import_error('xxe', 'XML 含 DTD 实体声明（'.self::short($path).'），为防止 XXE 已拒绝');
		}
		return $data;
	}

	private function load_xml($path) {
		$data = $this->xml_source($path, $this->limits['max_xml_dom_bytes']);
		$doc = new DOMDocument();
		$doc->resolveExternals = false;
		$doc->substituteEntities = false;
		libxml_clear_errors();
		if (!$doc->loadXML(self::named_entities($data), LIBXML_NONET | LIBXML_NOCDATA) || !$doc->documentElement) {
			throw new Epub_import_error('invalid_xml', 'XML 格式错误：'.self::short($path));
		}
		if ($doc->doctype && $doc->doctype->entities->length > 0) throw new Epub_import_error('xxe', 'XML 含实体声明，已拒绝');
		return $doc;
	}

	/** HTML named references (e.g. &nbsp;) become numeric references; unknown names stay and fail parsing. */
	private static function named_entities($data) {
		return preg_replace_callback('/&([A-Za-z][A-Za-z0-9]{1,31});/', function ($m) {
			if (in_array($m[1], array('amp', 'lt', 'gt', 'quot', 'apos'), true)) return $m[0];
			$text = html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($text === $m[0]) return $m[0];
			$out = '';
			foreach (mb_str_split($text, 1, 'UTF-8') as $char) $out .= '&#x'.dechex(mb_ord($char, 'UTF-8')).';';
			return $out;
		}, $data);
	}

	private function metadata($xp, $manifest) {
		$title = '';
		foreach ($xp->query('//*[local-name()="metadata"]/*[local-name()="title"]') as $node) {
			$title = self::clean($node->textContent);
			if ($title !== '') break;
		}
		$roles = array();
		foreach ($xp->query('//*[local-name()="metadata"]/*[local-name()="meta"][@property="role"]') as $node) {
			$roles[ltrim($node->getAttribute('refines'), '#')] = trim($node->textContent);
		}
		$authors = array();
		$others = array();
		foreach ($xp->query('//*[local-name()="metadata"]/*[local-name()="creator"]') as $node) {
			$name = self::clean($node->textContent);
			if ($name === '') continue;
			$role = $node->getAttributeNS(self::NS_OPF, 'role');
			if ($role === '' && isset($roles[$node->getAttribute('id')])) $role = $roles[$node->getAttribute('id')];
			if ($role === '' || $role === 'aut') $authors[] = $name; else $others[] = $name;
		}
		if (empty($authors)) $authors = $others;
		$language = '';
		$node = $xp->query('//*[local-name()="metadata"]/*[local-name()="language"]')->item(0);
		if ($node) $language = self::clean($node->textContent);
		$identifier = '';
		$node = $xp->query('//*[local-name()="metadata"]/*[local-name()="identifier"]')->item(0);
		if ($node) $identifier = self::clean($node->textContent);

		$cover = null;
		foreach ($manifest as $item) if (strpos($item['props'], ' cover-image ') !== false) { $cover = $item; break; }
		if (!$cover) {
			foreach ($xp->query('//*[local-name()="metadata"]/*[local-name()="meta"][@name="cover"]') as $node) {
				$id = $node->getAttribute('content');
				if (isset($manifest[$id])) { $cover = $manifest[$id]; break; }
			}
		}
		if ($cover && !isset($this->entries[$cover['path']])) {
			$this->warn('声明的封面文件不存在，未设置封面');
			$cover = null;
		}
		return array(
			'title' => mb_substr($title, 0, $this->limits['max_title_chars'], 'UTF-8'),
			'author' => mb_substr(implode('、', array_unique($authors)), 0, $this->limits['max_author_chars'], 'UTF-8'),
			'language' => mb_substr($language, 0, 32, 'UTF-8'),
			'identifier' => mb_substr($identifier, 0, 128, 'UTF-8'),
			'cover_path' => $cover ? $cover['path'] : '',
			'cover_type' => $cover ? $cover['type'] : '',
		);
	}

	private function nav_labels($path, &$labels, &$roles) {
		$doc = $this->load_xml($path);
		$xp = new DOMXPath($doc);
		$base = self::dirname($path);
		foreach ($xp->query('//*[local-name()="nav"]') as $nav) {
			$type = ' '.$nav->getAttributeNS(self::NS_OPS, 'type').' ';
			$is_toc = strpos($type, ' toc ') !== false;
			$is_landmarks = strpos($type, ' landmarks ') !== false;
			if (!$is_toc && !$is_landmarks) continue;
			foreach ($xp->query('.//*[local-name()="a"]', $nav) as $a) {
				$target = $this->link_target($base, $a->getAttribute('href'));
				if ($target === '') continue;
				if ($is_landmarks) {
					$role = trim($a->getAttributeNS(self::NS_OPS, 'type'));
					if ($role !== '' && !isset($roles[$target])) $roles[$target] = $role === 'cover' ? 'cover' : ($role === 'toc' ? 'toc' : $role);
					continue;
				}
				$label = self::clean($a->textContent);
				if ($label !== '' && !isset($labels[$target])) $labels[$target] = array('label' => self::title($label), 'from' => 'nav');
			}
		}
	}

	private function ncx_labels($path, &$labels) {
		$doc = $this->load_xml($path);
		$xp = new DOMXPath($doc);
		$base = self::dirname($path);
		foreach ($xp->query('//*[local-name()="navPoint"]') as $point) {
			$content = $xp->query('*[local-name()="content"]', $point)->item(0);
			$text = $xp->query('*[local-name()="navLabel"]/*[local-name()="text"]', $point)->item(0);
			if (!$content || !$text) continue;
			$target = $this->link_target($base, $content->getAttribute('src'));
			$label = self::clean($text->textContent);
			if ($target !== '' && $label !== '' && !isset($labels[$target])) $labels[$target] = array('label' => self::title($label), 'from' => 'ncx');
		}
	}

	private function link_target($base, $href) {
		if ($href === '' || self::is_remote($href) || $href[0] === '#') return '';
		try {
			return $this->resolve($base, $href);
		} catch (Epub_import_error $e) {
			if ($e->reason === 'path_traversal') throw $e;
			return '';
		}
	}

	// ---- content -----------------------------------------------------------------

	private function extract_xhtml($path) {
		$source = self::named_entities($this->xml_source($path, 0));
		$base = self::dirname($path);
		$reader = new XMLReader();
		if (!$reader->XML($source, null, LIBXML_NONET)) throw new Epub_import_error('invalid_xml', 'XHTML 无法解析：'.self::short($path));
		$reader->setParserProperty(XMLReader::LOADDTD, false);
		$reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);
		$blocks = array();
		$buffer = '';
		$heading = '';
		$heading_depth = 0;
		$heading_text = '';
		$in_body = false;
		$skip = 0;
		$pre = 0;
		$stack = array();
		libxml_clear_errors();
		while (true) {
			$ok = @$reader->read();
			if (!$ok) break;
			$type = $reader->nodeType;
			if ($type === XMLReader::DOC_TYPE && $reader->value !== '') throw new Epub_import_error('xxe', 'XHTML 含内部 DTD，已拒绝');
			if ($type === XMLReader::ELEMENT) {
				$name = strtolower($reader->localName);
				$empty = $reader->isEmptyElement;
				$this->check_attributes($reader, $path);
				if (in_array($name, self::$active, true)) throw new Epub_import_error('active_content', '正文含脚本或嵌入式活动内容（<'.$name.'>），不支持导入：'.self::short($path));
				if (!$empty) $stack[] = $name;
				if ($name === 'body') $in_body = true;
				if (in_array($name, self::$skip, true) || !$in_body) { if (!$empty && in_array($name, self::$skip, true)) $skip++; continue; }
				if ($skip > 0) continue;
				if (in_array($name, self::$blocks, true) || $name === 'br') $this->flush($blocks, $buffer, $pre > 0);
				if ($name === 'pre' && !$empty) $pre++;
				if (preg_match('/^h[1-6]$/', $name) && !$empty && $heading === '' && $heading_depth === 0) { $heading_depth = count($stack); $heading_text = ''; }
				$src = '';
				if ($name === 'img') $src = $reader->getAttribute('src');
				elseif ($name === 'image') { $src = $reader->getAttributeNs('href', 'http://www.w3.org/1999/xlink'); if (!$src) $src = $reader->getAttribute('href'); }
				if ($src !== null && $src !== '') {
					$this->flush($blocks, $buffer, $pre > 0);
					if (self::is_remote($src) || stripos(trim($src), 'data:') === 0) throw new Epub_import_error('remote_resource', '正文引用了外部或内联图片（'.self::short($src).'），仅支持 EPUB 内的本地图片');
					$image = $this->image($this->resolve($base, $src), '', false);
					if ($image) $this->add_block($blocks, array('type' => 'image', 'image' => $image['n'], 'alt' => self::clean((string)$reader->getAttribute('alt'))));
				}
			} elseif ($type === XMLReader::END_ELEMENT) {
				$name = strtolower($reader->localName);
				if ($heading_depth > 0 && count($stack) === $heading_depth) { $heading = self::title(self::clean($heading_text)); $heading_depth = 0; }
				array_pop($stack);
				if (in_array($name, self::$skip, true)) { if ($skip > 0) $skip--; continue; }
				if (!$in_body || $skip > 0) continue;
				if (in_array($name, self::$blocks, true)) $this->flush($blocks, $buffer, $pre > 0);
				if ($name === 'pre' && $pre > 0) $pre--;
				if ($name === 'body') $in_body = false;
			} elseif ($in_body && $skip === 0 && in_array($type, array(XMLReader::TEXT, XMLReader::CDATA, XMLReader::WHITESPACE, XMLReader::SIGNIFICANT_WHITESPACE), true)) {
				$buffer .= $reader->value;
				if ($heading_depth > 0) $heading_text .= $reader->value;
			}
		}
		$errors = libxml_get_errors();
		$reader->close();
		if (!empty($errors)) {
			foreach ($errors as $error) if ($error->level >= LIBXML_ERR_ERROR) throw new Epub_import_error('invalid_xml', 'XHTML 格式错误（第 '.$error->line.' 行）：'.self::short($path));
		}
		$this->flush($blocks, $buffer, false);
		return array('blocks' => $blocks, 'heading' => $heading);
	}

	private function check_attributes($reader, $path) {
		if (!$reader->hasAttributes) return;
		while ($reader->moveToNextAttribute()) {
			$name = strtolower($reader->localName);
			$value = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $reader->value));
			if (strpos($name, 'on') === 0) throw new Epub_import_error('unsafe_html', '正文含事件处理属性（'.$name.'），不支持导入：'.self::short($path));
			if (preg_match('/^(?:javascript|vbscript|data:text\/html|data:application|data:image\/svg)/', $value)) {
				throw new Epub_import_error('unsafe_html', '正文含脚本链接，不支持导入：'.self::short($path));
			}
			if (in_array($name, array('src', 'poster', 'srcset', 'background'), true) && self::is_remote($reader->value)) {
				throw new Epub_import_error('remote_resource', '正文引用了外部资源（'.self::short($reader->value).'），不会下载');
			}
		}
		$reader->moveToElement();
	}

	private function flush(&$blocks, &$buffer, $pre) {
		if ($buffer === '') return;
		// strtok yields one line at a time (empty lines are dropped anyway), so <pre> never expands into a full line array.
		for ($line = $pre ? strtok($buffer, "\r\n") : $buffer; $line !== false; $line = $pre ? strtok("\r\n") : false) {
			$text = self::clean($line);
			if ($text !== '' && preg_replace('/[\s\x{3000}\x{00a0}]+/u', '', $text) !== '') $this->add_block($blocks, array('type' => 'text', 'text' => $text));
		}
		$buffer = '';
	}

	/** Enforce block budgets before every append so floods of tiny elements are rejected, not allocated. */
	private function add_block(&$blocks, $block) {
		if (count($blocks) >= $this->limits['max_section_blocks']) {
			throw new Epub_import_error('too_complex', '单个分节的段落和图片超过 '.$this->limits['max_section_blocks'].' 个，请拆分章节后再导入');
		}
		if (++$this->block_total > $this->limits['max_book_blocks']) {
			throw new Epub_import_error('too_complex', '全书段落和图片超过 '.$this->limits['max_book_blocks'].' 个，请拆分后再导入');
		}
		$blocks[] = $block;
	}

	/** Validate and stage one raster image; unsupported vector images are excluded with a warning. */
	private function image($path, $declared, $is_cover) {
		if (isset($this->images[$path])) return $this->images[$path];
		$data = $this->read($path);
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$head = ltrim(substr($data, 0, 512));
		if ($declared === 'image/svg+xml' || $ext === 'svg' || stripos($head, '<svg') !== false || (stripos($head, '<?xml') === 0 && stripos($data, '<svg') !== false)) {
			$this->warn('SVG 图片不受支持，已排除：'.self::short($path));
			return null;
		}
		$info = @getimagesizefromstring($data);
		$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
		$sniffed = $finfo ? finfo_buffer($finfo, $data) : '';
		if ($finfo) finfo_close($finfo);
		$type = $info ? $info[2] : 0;
		if (!isset(self::$raster[$type]) || self::magic($data) !== $type || $sniffed !== self::$raster[$type][0]) {
			throw new Epub_import_error('invalid_image', ($is_cover ? '封面' : '图片').'不是有效的 JPEG/PNG/GIF/WebP 栅格图：'.self::short($path));
		}
		if (preg_match('/<\?php|<\?=|<script|<html/i', $data)) throw new Epub_import_error('invalid_image', '图片包含可疑脚本内容：'.self::short($path));
		if ($info[0] < 1 || $info[1] < 1 || $info[0] > $this->limits['max_image_side'] || $info[1] > $this->limits['max_image_side']
			|| $info[0] * $info[1] > $this->limits['max_image_pixels']) {
			throw new Epub_import_error('invalid_image', '图片尺寸超出限制：'.self::short($path));
		}
		// libgd keeps a 4-byte pixel buffer; PNG adds up to 8 bytes per pixel of raw rows, other decoders less.
		if ($info[0] * $info[1] * ($type === IMAGETYPE_PNG ? 12 : 8) > $this->limits['max_decode_bytes']) {
			throw new Epub_import_error('invalid_image', ($is_cover ? '封面' : '图片').'解码所需内存超过 '.self::mib($this->limits['max_decode_bytes']).'，请缩小尺寸：'.self::short($path));
		}
		$frames = self::intact($data, $type, $info[0], $info[1], $this->limits['max_image_pixels']);
		if ($frames === false || !self::decodes($frames)) {
			throw new Epub_import_error('invalid_image', ($is_cover ? '封面' : '图片').'数据已损坏或无法解码：'.self::short($path));
		}
		$mime = self::$raster[$type][0];
		if ($declared !== '' && $declared !== $mime && strpos($declared, 'image/') === 0) $this->warn('图片声明类型与实际不符，已按实际类型处理：'.self::short($path));
		$n = count($this->images);
		$file = $this->workdir.'img/'.$n.'.'.self::$raster[$type][1];
		if (file_put_contents($file, $data, LOCK_EX) !== strlen($data)) throw new Epub_import_error('server', '暂存图片写入失败');
		@chmod($file, 0600);
		$this->images[$path] = array('n' => $n, 'mime' => $mime, 'ext' => self::$raster[$type][1], 'width' => $info[0], 'height' => $info[1],
			'bytes' => strlen($data), 'source' => $path);
		return $this->images[$path];
	}

	/**
	 * Cheap bounded structure walk before any decoder runs. Returns the payloads libgd must decode
	 * (the image itself, or every animated WebP frame as a still WebP), or false.
	 */
	private static function intact($data, $type, $width, $height, $max_pixels) {
		if ($type === IMAGETYPE_PNG) return self::intact_png($data, $width, $height) ? array($data) : false;
		if ($type === IMAGETYPE_JPEG) return self::intact_jpeg($data) ? array($data) : false;
		if ($type === IMAGETYPE_GIF) return self::intact_gif($data, $max_pixels) ? array($data) : false;
		if ($type === IMAGETYPE_WEBP) return self::webp_frames($data, $width, $height, $max_pixels);
		return false;
	}

	/** Real decode with libgd; JPEG warnings (truncated or corrupt data) count as failure. */
	private static function decodes($payloads) {
		$previous = ini_set('gd.jpeg_ignore_warning', '0');
		set_error_handler(function () { return true; }, E_WARNING | E_NOTICE);
		try {
			foreach ($payloads as $payload) {
				$im = imagecreatefromstring($payload);
				if (!$im) return false;
				imagedestroy($im);
			}
			return true;
		} finally {
			restore_error_handler();
			ini_set('gd.jpeg_ignore_warning', $previous === false ? '1' : $previous);
		}
	}

	/** PNG: chunk lengths and CRCs, IHDR first, IEND last, and IDAT inflating to exactly the declared pixel rows. */
	private static function intact_png($data, $width, $height) {
		$len = strlen($data);
		$pos = 8;
		$expected = null;
		$inflate = null;
		$out = 0;
		while ($pos + 12 <= $len) {
			$n = unpack('N', substr($data, $pos, 4))[1];
			$kind = substr($data, $pos + 4, 4);
			if ($n > $len - $pos - 12) return false;
			$body = (string)substr($data, $pos + 8, $n);
			if (unpack('N', substr($data, $pos + 8 + $n, 4))[1] !== crc32($kind.$body)) return false;
			$pos += 12 + $n;
			if ($expected === null) {
				if ($kind !== 'IHDR' || $n !== 13) return false;
				$h = unpack('Nw/Nh/Cdepth/Ccolor/Ccompression/Cfilter/Cinterlace', $body);
				$channels = array(0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4);
				if ($h['w'] !== $width || $h['h'] !== $height || !isset($channels[$h['color']]) || !in_array($h['depth'], array(1, 2, 4, 8, 16), true)
					|| $h['compression'] !== 0 || $h['filter'] !== 0 || $h['interlace'] > 1) return false;
				$bits = $h['depth'] * $channels[$h['color']];
				$passes = $h['interlace'] ? array(array(0, 0, 8, 8), array(4, 0, 8, 8), array(0, 4, 4, 8), array(2, 0, 4, 4), array(0, 2, 2, 4), array(1, 0, 2, 2), array(0, 1, 1, 2))
					: array(array(0, 0, 1, 1));
				$expected = 0;
				foreach ($passes as $p) {
					$pw = $width > $p[0] ? intdiv($width - $p[0] + $p[2] - 1, $p[2]) : 0;
					$ph = $height > $p[1] ? intdiv($height - $p[1] + $p[3] - 1, $p[3]) : 0;
					if ($pw > 0 && $ph > 0) $expected += $ph * (1 + intdiv($pw * $bits + 7, 8));
				}
			} elseif ($kind === 'IDAT') {
				if ($inflate === null) $inflate = inflate_init(ZLIB_ENCODING_DEFLATE);
				// Small input slices cap each inflate step (~4 MiB at zlib's maximum ratio); output is counted, never kept.
				for ($i = 0; $i < $n; $i += 4096) {
					$chunk = @inflate_add($inflate, substr($body, $i, 4096), ZLIB_SYNC_FLUSH);
					if ($chunk === false) return false;
					$out += strlen($chunk);
					if ($out > $expected) return false;
				}
			} elseif ($kind === 'IEND') {
				if ($n !== 0 || $pos !== $len || $inflate === null) return false;
				if (inflate_get_status($inflate) !== ZLIB_STREAM_END) {
					$tail = @inflate_add($inflate, '', ZLIB_FINISH);
					if ($tail === false) return false;
					$out += strlen($tail);
				}
				return $out === $expected && inflate_get_status($inflate) === ZLIB_STREAM_END;
			}
		}
		return false;
	}

	/** JPEG: well-formed marker segments, a frame header, non-empty entropy-coded scans and a final EOI. */
	private static function intact_jpeg($data) {
		$len = strlen($data);
		$pos = 2;
		$frame = false;
		$scan = false;
		while ($pos + 2 <= $len) {
			if ($data[$pos] !== "\xFF") return false;
			$marker = ord($data[$pos + 1]);
			if ($marker === 0xFF) { $pos++; continue; }
			if ($marker === 0xD9) return $frame && $scan && trim((string)substr($data, $pos + 2), "\0") === '';
			if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) { $pos += 2; continue; }
			if ($pos + 4 > $len) return false;
			$size = unpack('n', substr($data, $pos + 2, 2))[1];
			if ($size < 2 || $pos + 2 + $size > $len) return false;
			if ($marker >= 0xC0 && $marker <= 0xCF && !in_array($marker, array(0xC4, 0xC8, 0xCC), true)) {
				if ($size < 8) return false;
				$frame = true;
			}
			$pos += 2 + $size;
			if ($marker === 0xDA) {
				if (!$frame) return false;
				$start = $pos;
				while (true) {
					$pos = strpos($data, "\xFF", $pos);
					if ($pos === false || $pos + 1 >= $len) return false;
					$next = ord($data[$pos + 1]);
					if ($next === 0x00 || ($next >= 0xD0 && $next <= 0xD7)) { $pos += 2; continue; }
					if ($next === 0xFF) { $pos++; continue; }
					break;
				}
				if ($pos === $start) return false;
				$scan = true;
			}
		}
		return false;
	}

	/**
	 * GIF: colour tables, extension and image blocks with terminated sub-blocks, then the trailer. libgd silently
	 * accepts broken LZW data, so every frame's code stream is also run (lengths only, no pixel buffer).
	 */
	private static function intact_gif($data, $max_pixels) {
		$len = strlen($data);
		if ($len < 14) return false;
		$pos = 13;
		$flags = ord($data[10]);
		if ($flags & 0x80) $pos += 3 << (($flags & 7) + 1);
		$frames = 0;
		$total = 0;
		while ($pos < $len) {
			$block = $data[$pos];
			if ($block === ';') return $frames > 0;
			if ($block === '!') {
				$pos += 2;
				$image = false;
			} elseif ($block === ',') {
				if ($pos + 11 > $len) return false;
				$local = ord($data[$pos + 9]);
				$pixels = unpack('v', substr($data, $pos + 5, 2))[1] * unpack('v', substr($data, $pos + 7, 2))[1];
				$pos += 10;
				if ($local & 0x80) $pos += 3 << (($local & 7) + 1);
				if ($pos >= $len) return false;
				$code = ord($data[$pos]);
				if ($code < 2 || $code > 8) return false;
				$total += $pixels;
				if ($pixels === 0 || $total > $max_pixels) return false;
				$pos++;
				$image = true;
			} else {
				return false;
			}
			$stream = '';
			while (true) {
				if ($pos >= $len) return false;
				$n = ord($data[$pos]);
				if ($image) $stream .= (string)substr($data, $pos + 1, $n);
				$pos += 1 + $n;
				if ($n === 0) break;
			}
			if ($image) {
				if ($stream === '' || !self::gif_lzw($stream, $code, $pixels)) return false;
				$frames++;
			}
		}
		return false;
	}

	/** Every LZW code must be defined when read and the stream must yield at least $pixels colour indices. */
	private static function gif_lzw($stream, $min, $pixels) {
		$clear = 1 << $min;
		$lengths = array_fill(0, $clear, 1);
		$size = $min + 1;
		$next = $clear + 2;
		$prev = -1;
		$out = 0;
		$bits = strlen($stream) * 8;
		$bit = 0;
		$stream .= "\0\0\0";
		while ($out < $pixels && $bit + $size <= $bits) {
			$p = $bit >> 3;
			$code = ((ord($stream[$p]) | (ord($stream[$p + 1]) << 8) | (ord($stream[$p + 2]) << 16)) >> ($bit & 7)) & ((1 << $size) - 1);
			$bit += $size;
			if ($code === $clear) {
				$size = $min + 1;
				$next = $clear + 2;
				$prev = -1;
				continue;
			}
			if ($code === $clear + 1) break;
			if ($prev < 0) {
				if ($code > $clear) return false;
				$out++;
				$prev = $code;
				continue;
			}
			if ($code < $next) $n = $lengths[$code];
			elseif ($code === $next && $next < 4096) $n = $lengths[$prev] + 1;
			else return false;
			if ($next < 4096) {
				$lengths[$next] = $lengths[$prev] + 1;
				$next++;
				if ($next === (1 << $size) && $size < 12) $size++;
			}
			$out += $n;
			$prev = $code;
		}
		return $out >= $pixels;
	}

	/**
	 * WebP: RIFF size matches the payload and chunks tile it exactly. Returns the still image, or each
	 * ANMF frame rewrapped as a still WebP (with VP8X + ALPH when it has alpha) so libwebp decodes every frame.
	 */
	private static function webp_frames($data, $width, $height, $max_pixels) {
		$len = strlen($data);
		if ($len < 20 || unpack('V', substr($data, 4, 4))[1] + 8 !== $len) return false;
		$chunks = self::riff_chunks($data, 12);
		if ($chunks === false) return false;
		$still = false;
		$frames = array();
		$total = 0;
		foreach ($chunks as $chunk) {
			if ($chunk[0] === 'VP8 ' || $chunk[0] === 'VP8L') {
				// The decoder allocates for the bitstream's own size, so it must be the size that was budgeted.
				if (self::webp_size($chunk[0], $chunk[1]) !== array($width, $height)) return false;
				$still = true;
			}
			if ($chunk[0] !== 'ANMF') continue;
			$f = $chunk[1];
			if (strlen($f) < 24) return false;
			$x = 2 * (ord($f[0]) | ord($f[1]) << 8 | ord($f[2]) << 16);
			$y = 2 * (ord($f[3]) | ord($f[4]) << 8 | ord($f[5]) << 16);
			$w = 1 + (ord($f[6]) | ord($f[7]) << 8 | ord($f[8]) << 16);
			$h = 1 + (ord($f[9]) | ord($f[10]) << 8 | ord($f[11]) << 16);
			$total += $w * $h;
			if ($x + $w > $width || $y + $h > $height || $total > $max_pixels) return false;
			$inner = self::riff_chunks($f, 16);
			if ($inner === false) return false;
			$body = '';
			$alpha = false;
			$bitstream = false;
			foreach ($inner as $c) {
				if ($c[0] !== 'ALPH' && $c[0] !== 'VP8 ' && $c[0] !== 'VP8L') continue;
				$alpha = $alpha || $c[0] === 'ALPH';
				if ($c[0] !== 'ALPH') {
					if ($bitstream || self::webp_size($c[0], $c[1]) !== array($w, $h)) return false;
					$bitstream = true;
				}
				$body .= $c[0].pack('V', strlen($c[1])).$c[1].(strlen($c[1]) & 1 ? "\0" : '');
			}
			if (!$bitstream) return false;
			if ($alpha) $body = 'VP8X'.pack('V', 10)."\x10\0\0\0".substr(pack('V', $w - 1), 0, 3).substr(pack('V', $h - 1), 0, 3).$body;
			$frames[] = 'RIFF'.pack('V', 4 + strlen($body)).'WEBP'.$body;
		}
		if ($frames) return $frames;
		return $still ? array($data) : false;
	}

	/** Width and height from a VP8 key-frame header or a VP8L header, or false. */
	private static function webp_size($kind, $data) {
		if ($kind === 'VP8 ') {
			if (strlen($data) < 10 || (ord($data[0]) & 1) || substr($data, 3, 3) !== "\x9d\x01\x2a") return false;
			return array(unpack('v', substr($data, 6, 2))[1] & 0x3fff, unpack('v', substr($data, 8, 2))[1] & 0x3fff);
		}
		if (strlen($data) < 5 || ord($data[0]) !== 0x2f) return false;
		$bits = unpack('V', substr($data, 1, 4))[1];
		return array(($bits & 0x3fff) + 1, (($bits >> 14) & 0x3fff) + 1);
	}

	/** Split RIFF chunks from $pos to the end; false unless they tile the buffer exactly (with even padding). */
	private static function riff_chunks($data, $pos) {
		$len = strlen($data);
		$chunks = array();
		while ($pos + 8 <= $len) {
			$size = unpack('V', substr($data, $pos + 4, 4))[1];
			if ($size > $len - $pos - 8) return false;
			$chunks[] = array(substr($data, $pos, 4), (string)substr($data, $pos + 8, $size));
			$pos += 8 + $size + ($size & 1);
		}
		return $pos === $len ? $chunks : false;
	}

	private static function magic($data) {
		if (strncmp($data, "\xFF\xD8\xFF", 3) === 0) return IMAGETYPE_JPEG;
		if (strncmp($data, "\x89PNG\r\n\x1a\n", 8) === 0) return IMAGETYPE_PNG;
		if (strncmp($data, 'GIF87a', 6) === 0 || strncmp($data, 'GIF89a', 6) === 0) return IMAGETYPE_GIF;
		if (strncmp($data, 'RIFF', 4) === 0 && substr($data, 8, 4) === 'WEBP') return IMAGETYPE_WEBP;
		return 0;
	}

	// ---- paths and text ---------------------------------------------------------

	/** Resolve a document-relative URI reference to a normalized archive path without leaving the root. */
	public function resolve($base, $href) {
		$href = preg_replace('/[#?].*$/s', '', trim($href));
		if ($href === '') throw new Epub_import_error('invalid_path', '资源路径为空');
		$decoded = rawurldecode($href);
		if (!mb_check_encoding($decoded, 'UTF-8') || preg_match('/[\x00-\x1f\\\\]/', $decoded)) throw new Epub_import_error('invalid_path', '资源路径无效：'.self::short($href));
		if ($decoded[0] === '/' || preg_match('#^[A-Za-z]:#', $decoded)) throw new Epub_import_error('absolute_path', '资源使用绝对路径：'.self::short($href));
		$parts = $base === '' ? array() : explode('/', $base);
		foreach (explode('/', $decoded) as $part) {
			if ($part === '' || $part === '.') continue;
			if ($part === '..') {
				if (empty($parts)) throw new Epub_import_error('path_traversal', '资源路径越出 EPUB 根目录：'.self::short($href));
				array_pop($parts);
			} else {
				$parts[] = $part;
			}
		}
		if (empty($parts)) throw new Epub_import_error('invalid_path', '资源路径无效：'.self::short($href));
		return implode('/', $parts);
	}

	public static function is_remote($href) {
		$href = trim($href);
		return strpos($href, '//') === 0 || (bool)preg_match('#^[A-Za-z][A-Za-z0-9+.-]*:#', $href);
	}

	private static function dirname($path) {
		$pos = strrpos($path, '/');
		return $pos === false ? '' : substr($path, 0, $pos);
	}

	public static function clean($text) {
		$text = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u', '', (string)$text);
		$text = preg_replace('/[ \t\r\n\f]+/', ' ', $text);
		return trim($text, " \t\r\n\f");
	}

	private static function title($text) {
		return mb_substr($text, 0, 128, 'UTF-8');
	}

	private static function short($text) {
		$text = preg_replace('/[\x00-\x1f\x7f]/', '', (string)$text);
		return mb_strlen($text, 'UTF-8') > 80 ? mb_substr($text, 0, 80, 'UTF-8').'…' : $text;
	}

	private static function mib($bytes) {
		return round($bytes / 1048576).' MiB';
	}

	private function warn($message) {
		if (count($this->warnings) < 50) $this->warnings[] = $message;
	}

	private function write_json($file, $value) {
		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
		if ($json === false || file_put_contents($file, $json, LOCK_EX) !== strlen($json)) throw new Epub_import_error('server', '暂存分节写入失败');
		@chmod($file, 0600);
	}
}
