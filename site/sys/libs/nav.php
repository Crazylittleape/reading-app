<?php if (!defined('FCPATH')) exit('No direct script access allowed');
return array (
  array (
    'name' => '小说管理',
    'icon' => '&#xe705;',
    'file' => 'book/',
    'list' => 
    array (
      array (
        'name' => '小说列表',
        'url' => 'book/index',
        'init' => 1,
      ),
      array (
        'name' => '小说修改',
        'url' => 'book/edit,book/save,book/init,book/del',
        'init' => 0,
      ),
      array (
        'name' => '小说删除',
        'url' => 'book/del',
        'init' => 0,
      ),
      array (
        'name' => '章节浏览',
        'url' => 'book/chapter,book/chapter_ajax',
        'init' => 0,
      ),
      array (
        'name' => '章节操作',
        'url' => 'book/chapter_edit,book/chapter_init/vip,book/chapter_init/cion,book/chapter_init/px,book/chapter_save,book/chapter_del,book/chapter_txt',
        'init' => 0,
      ),
      array (
        'name' => 'EPUB导入',
        'url' => 'import/index,import/upload,import/preview,import/status,import/section,import/image,import/save,import/delete,import/cleanup',
        'init' => 1,
      ),
      array (
        'name' => '分类列表',
        'url' => 'book/lists',
        'init' => 1,
      ),
      array (
        'name' => '分类修改',
        'url' => 'book/lists_edit,book/lists_save,book/lists_edit_save',
        'init' => 0,
      ),
      array (
        'name' => '分类删除',
        'url' => 'book/lists_del',
        'init' => 0,
      ),
    ),
  )
);
