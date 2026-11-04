<?php if (!defined('FCPATH')) exit('No direct script access allowed');
return array (
  'apikey' => reading_required_env('APP_API_KEY'),
  'update' => 
  array (
    'android' => 
    array (
      'version' => '1.0.0',
      'downurl' => '',
      'force' => '1',
      'text' => '1.新版本发布',
    ),
    'ios' => 
    array (
      'version' => '1.0.0',
      'downurl' => '',
      'force' => '1',
      'text' => '1.新版本发布',
    ),
  ),
  'pay' => 
  array (
    'vip' => 
    array (
      0 => 
      array (
        'day' => '30',
        'rmb' => '25',
      ),
      1 => 
      array (
        'day' => '90',
        'rmb' => '60',
      ),
      2 => 
      array (
        'day' => '180',
        'rmb' => '108',
      ),
      3 => 
      array (
        'day' => '365',
        'rmb' => '198',
      ),
    ),
    'cion' => 
    array (
      0 => 
      array (
        'cion' => '100',
        'rmb' => '1',
      ),
      1 => 
      array (
        'cion' => '800',
        'rmb' => '8',
      ),
      2 => 
      array (
        'cion' => '1200',
        'rmb' => '12',
      ),
      3 => 
      array (
        'cion' => '1800',
        'rmb' => '18',
      ),
      4 => 
      array (
        'cion' => '5000',
        'rmb' => '50',
      ),
      5 => 
      array (
        'cion' => '9800',
        'rmb' => '98',
      ),
    ),
    'ticket' => 
    array (
      0 => 
      array (
        'nums' => '1',
        'rmb' => '1',
      ),
      1 => 
      array (
        'nums' => '5',
        'rmb' => '5',
      ),
      2 => 
      array (
        'nums' => '12',
        'rmb' => '12',
      ),
      3 => 
      array (
        'nums' => '18',
        'rmb' => '18',
      ),
      4 => 
      array (
        'nums' => '50',
        'rmb' => '50',
      ),
      5 => 
      array (
        'nums' => '98',
        'rmb' => '98',
      ),
    ),
  ),
  'search' => 
  array(),
  'book_search' => 
  array(),
  'html' => 
  array (
    'agreement' => '&lt;p&gt;&lt;b&gt;用户协议&lt;/b&gt;&lt;/p&gt;&lt;p&gt;用户协议自行后台修改&lt;/p&gt;',
    'privacy' => '&lt;p&gt;&lt;b&gt;隐私政策&lt;/b&gt;&lt;/p&gt;&lt;p&gt;隐私政策自行后台修改&lt;/p&gt;',
  ),
);