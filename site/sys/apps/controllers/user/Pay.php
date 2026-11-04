<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends Mccms_Controller {
	public function __construct(){
		parent::__construct();
	}

	//购买
    public function index($op='') {
        $this->users->login();//判断登陆
        $data = array();
        $data['mccms_title'] = '在线购买充值 - '.Web_Name;
    	$uid = (int)$this->cookie->get('user_id');
    	$row = $this->mcdb->get_row_arr('user','*',array('id'=>$uid));
        $data['user_id'] = $uid;
        //获取模版
        $tpl = !empty($op) ? 'pay_'.$op.'.html' : 'pay.html';
        $str = load_file('user/'.$tpl);
        //全局解析
        $str = $this->parser->parse_string($str,$data,true);
        //会员数据
        $str = $this->parser->mccms_tpl('user',$str,$str,$row);
        //IF判断解析
        echo $this->parser->labelif($str);
	}
}