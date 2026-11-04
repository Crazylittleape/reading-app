<?php 
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logout extends Mccms_Controller {
	
	function __construct() {
	    parent::__construct();
	}

	public function index()
	{
		$this->cookie->set('admin_id', '');
		$this->cookie->set('admin_nichen', '');
		$this->cookie->set('admin_login', '');
		header("location:".links('login'));exit;
	}
}