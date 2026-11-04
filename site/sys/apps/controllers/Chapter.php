<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Chapter extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

    public function index($mid=0,$id=0) {
    	$mid = (int)$mid;
    	$id = (int)$id;
    	if($id == 0) $id = $mid;
    	if(empty($id)) get_err();
    	//判断纯静态
    	if(Url_Mode > 0){
    		header("location:".get_url('pic',array('id'=>$id,'en'=>$id)));
    		exit;
    	}
        $cache_id = 'chapter-'.$id;
        if(!$this->caches->start($cache_id,Cache_Time_Pic)){
    	   echo $this->tpl->chapter($id);
           $this->caches->end();
        }
	}
}