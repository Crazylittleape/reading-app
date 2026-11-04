<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');
class Comic extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

    public function index($id='') {
        if(!ctype_alnum($id)) get_err('comic');
    	//判断纯静态
    	if(Url_Mode > 0){
            if(is_numeric($id)){
                $en = getzd('comic','yname',$id);
            }else{
                $en = $id;
                $id = getzd('comic','id',$id,'yname');
            }
    		header("location:".get_url('show',array('id'=>$id,'yname'=>$id)));
    		exit;
    	}
        $cache_id = 'comic-'.$id;
        if(!$this->caches->start($cache_id,Cache_Time_Show)){
    	   echo $this->tpl->comic($id);
           $this->caches->end();
        }
	}
}