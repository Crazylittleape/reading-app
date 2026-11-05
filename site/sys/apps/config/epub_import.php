<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * EPUB import limits. These are the single source of truth for the import
 * controller and parser; php.ini, Apache LimitRequestBody and Nginx
 * client_max_body_size must stay above max_upload_bytes (see tests).
 */
$config['epub_import'] = array(
	'max_upload_bytes' => 10485760,        // 10 MiB uploaded archive
	'max_entries' => 2000,                 // ZIP entries, directories included
	'max_total_bytes' => 104857600,        // 100 MiB of declared and actually read data
	'max_entry_bytes' => 10485760,         // 10 MiB per entry
	'max_xml_dom_bytes' => 4194304,        // container/OPF/NCX/nav/encryption documents
	'max_image_pixels' => 40000000,
	'max_image_side' => 20000,
	'max_decode_bytes' => 134217728,      // estimated decoder memory per image (pixels x 12 for PNG, x 8 otherwise)
	'max_sections' => 2000,
	'max_xml_tags' => 200000,             // markup tags per XML/XHTML document, counted before any parsing
	'max_section_blocks' => 20000,        // paragraphs + images kept for one section
	'max_book_blocks' => 200000,          // paragraphs + images across the whole book
	'staging_ttl' => 86400,                // private preview lifetime in seconds
	'staging_max_per_admin' => 10,
	'max_title_chars' => 128,
	'max_author_chars' => 64,
	'csrf_ttl' => 43200,
);
