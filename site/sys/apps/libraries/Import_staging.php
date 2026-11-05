<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/Epub_parser.php';
/**
 * Private, expiring EPUB preview staging under caches/import/a{admin}/{id}/.
 * caches/ is outside the public upload tree and denied by Apache. Stages are
 * always looked up under the authenticated administrator's own directory and
 * the owner recorded in meta.json, so an id alone never grants access.
 *
 * Every state change of one administrator's staging (quota reservation,
 * commit, save, delete, expiry purge) holds that administrator's flock on
 * a{admin}/.lock. Deletion first renames the stage out of the id namespace,
 * so a deleted preview is never listed or loadable with partial content.
 */
class Import_staging {
	const TMP_TTL = 3600;
	private $root;
	private $limits;

	public function __construct($limits, $root = '') {
		$this->limits = $limits;
		$this->root = $root !== '' ? rtrim($root, '/').'/' : FCPATH.'caches/import/';
	}

	public static function valid_id($id) {
		return is_string($id) && preg_match('/^[a-f0-9]{32}$/', $id) === 1;
	}

	private function admin_dir($admin_id) {
		return $this->root.'a'.intval($admin_id).'/';
	}

	/** Run $fn while holding the administrator's exclusive staging lock. */
	private function locked($admin_id, $fn) {
		$dir = $this->admin_dir($admin_id);
		if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) throw new Epub_import_error('server', '无法创建私有暂存目录');
		$fp = @fopen($dir.'.lock', 'c');
		if (!$fp || !flock($fp, LOCK_EX)) {
			if ($fp) fclose($fp);
			throw new Epub_import_error('server', '无法锁定私有暂存目录');
		}
		try {
			return $fn($dir);
		} finally {
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	public function create($admin_id, $file, $original_name) {
		$this->purge_expired();
		$id = bin2hex(random_bytes(16));
		$work = $this->admin_dir($admin_id).'.tmp-'.$id.'/';
		// Reserve a quota slot: finished stages plus in-flight uploads count.
		$this->locked($admin_id, function ($dir) use ($work) {
			if ($this->reserved($dir) >= $this->limits['staging_max_per_admin']) {
				throw new Epub_import_error('quota', '预览暂存已达 '.$this->limits['staging_max_per_admin'].' 个，请先删除不需要的预览');
			}
			if (!@mkdir($work.'sec', 0700, true) || !@mkdir($work.'img', 0700)) throw new Epub_import_error('server', '无法创建私有暂存目录');
		});
		try {
			$parser = new Epub_parser($this->limits);
			$book = $parser->parse($file, $work);
			$now = time();
			$sections = array();
			foreach ($book['sections'] as $section) $sections[] = array('title' => $section['title'], 'include' => $section['include']);
			$meta = array(
				'id' => $id,
				'owner' => intval($admin_id),
				'file' => mb_substr(Epub_parser::clean(basename(str_replace('\\', '/', (string)$original_name))), 0, 120, 'UTF-8'),
				'sha256' => hash_file('sha256', $file),
				'bytes' => filesize($file),
				'created' => $now,
				'expires' => $now + $this->limits['staging_ttl'],
				'book' => $book,
				'edit' => array('title' => $book['title'], 'author' => $book['author'], 'cid' => 0, 'sections' => $sections, 'saved' => 0),
			);
			$this->write_meta($work, $meta);
			$this->locked($admin_id, function ($dir) use ($work, $id) {
				if (!@rename(rtrim($work, '/'), $dir.$id)) throw new Epub_import_error('server', '暂存目录提交失败');
			});
			return $meta;
		} catch (Throwable $e) {
			$this->rmtree($work);
			throw $e;
		}
	}

	private function reserved($dir) {
		$count = 0;
		$now = time();
		foreach (scandir($dir) as $name) {
			if (self::valid_id($name) && is_file($dir.$name.'/meta.json')) $count++;
			elseif (strpos($name, '.tmp-') === 0 && @filemtime($dir.$name) >= $now - self::TMP_TTL) $count++;
		}
		return $count;
	}

	public function load($admin_id, $id) {
		if (!self::valid_id($id) || intval($admin_id) <= 0) return null;
		$dir = $this->admin_dir($admin_id).$id.'/';
		if (is_link(rtrim($dir, '/')) || !is_file($dir.'meta.json')) return null;
		$meta = json_decode((string)@file_get_contents($dir.'meta.json'), true);
		if (!is_array($meta) || !isset($meta['owner'], $meta['id']) || $meta['owner'] !== intval($admin_id) || $meta['id'] !== $id) return null;
		if ($meta['expires'] < time()) return null;
		return $meta;
	}

	/** Persist edits; returns false if the stage was deleted or expired meanwhile. */
	public function save($meta, $edit) {
		return $this->locked($meta['owner'], function () use ($meta, $edit) {
			$current = $this->load($meta['owner'], $meta['id']);
			if (!$current) return false;
			$current['edit'] = $edit;
			$this->write_meta($this->admin_dir($meta['owner']).$meta['id'].'/', $current);
			return true;
		});
	}

	public function section($meta, $n) {
		$file = $this->admin_dir($meta['owner']).$meta['id'].'/sec/'.intval($n).'.json';
		if (!is_file($file)) return null;
		$blocks = json_decode((string)@file_get_contents($file), true);
		return is_array($blocks) ? $blocks : null;
	}

	public function image_file($meta, $n) {
		foreach ($meta['book']['images'] as $image) {
			if ($image['n'] === intval($n)) {
				$file = $this->admin_dir($meta['owner']).$meta['id'].'/img/'.$image['n'].'.'.$image['ext'];
				return is_file($file) && !is_link($file) ? array('file' => $file, 'mime' => $image['mime']) : null;
			}
		}
		return null;
	}

	public function remove($admin_id, $id) {
		if (!self::valid_id($id) || intval($admin_id) <= 0) return false;
		return $this->locked($admin_id, function ($dir) use ($admin_id, $id) {
			if (!$this->load($admin_id, $id)) return false;
			return $this->discard($dir, $id);
		});
	}

	/** Atomically detach a stage from the id namespace, then delete it. Caller holds the lock. */
	private function discard($dir, $name) {
		$trash = $dir.'.del-'.bin2hex(random_bytes(8));
		if (!@rename($dir.$name, $trash)) return false;
		$this->rmtree($trash);
		return true;
	}

	public function listing($admin_id) {
		$rows = array();
		$dir = $this->admin_dir($admin_id);
		if (!is_dir($dir)) return $rows;
		foreach (scandir($dir) as $name) {
			if (!self::valid_id($name)) continue;
			$meta = $this->load($admin_id, $name);
			if ($meta) $rows[] = array('id' => $name, 'file' => $meta['file'], 'title' => $meta['edit']['title'],
				'created' => $meta['created'], 'expires' => $meta['expires'], 'sections' => count($meta['book']['sections']));
		}
		usort($rows, function ($a, $b) { return $b['created'] - $a['created']; });
		return $rows;
	}

	/** Remove expired stages of every administrator plus abandoned temporary and trash directories. */
	public function purge_expired() {
		$removed = 0;
		if (!is_dir($this->root)) return 0;
		foreach (scandir($this->root) as $owner) {
			if (!preg_match('/^a(\d+)$/', $owner, $m) || !is_dir($this->root.$owner) || is_link($this->root.$owner)) continue;
			$removed += $this->locked(intval($m[1]), function ($dir) {
				$count = 0;
				$now = time();
				foreach (scandir($dir) as $name) {
					if ($name === '.' || $name === '..' || $name === '.lock') continue;
					if (strpos($name, '.del-') === 0) { $this->rmtree($dir.$name); continue; }
					if (strpos($name, '.tmp-') === 0) {
						if (@filemtime($dir.$name) < $now - self::TMP_TTL) { $this->rmtree($dir.$name); $count++; }
						continue;
					}
					$meta = is_file($dir.$name.'/meta.json') ? json_decode((string)@file_get_contents($dir.$name.'/meta.json'), true) : null;
					if (!is_array($meta) || !isset($meta['expires']) || $meta['expires'] < $now) {
						if ($this->discard($dir, $name)) $count++;
					}
				}
				return $count;
			});
		}
		return $removed;
	}

	private function write_meta($dir, $meta) {
		$json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
		$tmp = $dir.'meta.json.'.bin2hex(random_bytes(4));
		if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) !== strlen($json) || !@rename($tmp, $dir.'meta.json')) {
			@unlink($tmp);
			throw new Epub_import_error('server', '暂存元数据写入失败');
		}
		@chmod($dir.'meta.json', 0600);
	}

	private function rmtree($path) {
		$path = rtrim($path, '/');
		if (strpos($path.'/', $this->root) !== 0 || $path.'/' === $this->root) return;
		if (is_link($path) || is_file($path)) { @unlink($path); return; }
		if (!is_dir($path)) return;
		foreach (scandir($path) as $name) {
			if ($name !== '.' && $name !== '..') $this->rmtree($path.'/'.$name);
		}
		@rmdir($path);
	}
}
