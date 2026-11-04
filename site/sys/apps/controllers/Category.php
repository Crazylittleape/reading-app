<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Category extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

    //智能检索
    public function index() {
        $uri = $this->uri->uri_string();
        $n = strpos($uri,'/index') !== false ? 3 : 2;
        $arr = safe_replace($this->uri->uri_to_assoc($n));
        echo $this->tpl->category($arr);
	}
}