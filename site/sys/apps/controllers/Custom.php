<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Custom extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

    public function index($op='') {
    	if($op == '') get_show_404();
    	//判断纯静态
    	if(Url_Mode > 0){
            $op = str_replace('.html','',$op);
    		header("location:".get_url('custom',array('file'=>$op)));
    		exit;
    	}
        if(!$this->caches->start('custom_'.$op)){
    	   echo $this->tpl->custom($op);
           $this->caches->end();
        }
	}
}