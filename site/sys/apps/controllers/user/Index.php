<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends Mccms_Controller {
	public function __construct(){
		parent::__construct();
        //判断登陆
		$this->users->login();
	}

	//中心主页
    public function index() {
        $data = array();
        $data['mccms_title'] = '读者中心 - '.Web_Name;
    	$uid = (int)$this->cookie->get('user_id');
    	$row = $this->mcdb->get_row_arr('user','*',array('id'=>$uid));
        //当前数据
        foreach ($row as $key => $val) $data['user_'.$key] = $val;
        $str = load_file('user/index.html');
        //全局解析
        $str = $this->parser->parse_string($str,$data,true);
        //会员数据
        $str = $this->parser->mccms_tpl('user',$str,$str,$row);
        //IF判断解析
        echo $this->parser->labelif($str);
	}
}