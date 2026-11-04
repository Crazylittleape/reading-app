<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Search extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

    //按关键字检索
    public function index($key='',$page=1) {
        $page = (int)$page;
        if($page == 0) $page =1;
        if(empty($key)) $key = $this->input->get_post('key',true);
        $key = safe_replace(urldecode($key));
        echo $this->tpl->search($key,$page);
    }
}