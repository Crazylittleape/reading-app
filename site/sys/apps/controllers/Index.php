<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Index extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
    }

	//主页
    public function index() {
    	//判断纯静态
    	if(Url_Mode > 0){
    		header("location:".get_url('index'));
    		exit;
    	}
    	if(!$this->caches->start('index',Cache_Time_Index)){
    		echo $this->tpl->index();
    		$this->caches->end();
    	}
	}
}