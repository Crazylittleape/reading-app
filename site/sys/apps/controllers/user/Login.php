<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends Mccms_Controller {
	public function __construct(){
		parent::__construct();
	}

	//会员登陆
    public function index() {
        $str = load_file('user/login.html');
        $str = $this->parser->parse_string($str,array(),true);
        //IF判断解析
        echo $this->parser->labelif($str);
	}

	//会员注册
    public function reg() {
        $str = load_file('user/reg.html');
        $str = $this->parser->parse_string($str,array(),true);
        //IF判断解析
        echo $this->parser->labelif($str);
	}

	//找回密码
    public function pass() {
        $str = load_file('user/pass.html');
        $str = $this->parser->parse_string($str,array(),true);
        //IF判断解析
        echo $this->parser->labelif($str);
	}
}