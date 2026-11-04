<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
if (!defined('BASEPATH')) exit('No direct script access allowed');
class Admin extends CI_Model
{
    function __construct (){
        parent:: __construct ();
        //判断IP白名单
        if(Admin_Ip != ''){
            $ip = getip();
            $iparr = explode('|', Admin_Ip);
            if(!in_array($ip, $iparr)){
                show_404();
            }
        }
    }
	
    //判断后台是否登入
    function login($sid=0,$key=''){
        if(empty($key)){
            $id = !$this->cookie->get('admin_id') ? 0 : $this->cookie->get('admin_id');
            $login =  !$this->cookie->get('admin_login') ? '' :  $this->cookie->get('admin_login');
        }else{
            $str  = sys_auth($key,1);
            $id   = isset($str['id'])?intval($str['id']) : 0;
            $login = isset($str['login'])?$str['login'] : '';
        }
        $islog = false;
        if(!empty($id) && !empty($login)){
            $admin = $this->mcdb->get_row('admin','name,pass',array('id'=>$id));
            if($admin && md5($id.$admin->name.$admin->pass.Admin_Code) == $login){
                $islog = true;
            }
        }
        if($sid > 0){
            return $islog;
        }else{
            //未登录
            if(!$islog){
                $this->cookie->set('admin_id');
                $this->cookie->set('admin_nichen');
                $this->cookie->set('admin_login');
                //判断直接打开还是ajax
                if(strpos($_SERVER['HTTP_ACCEPT'],'application/json') === false){
                    die("<script language='javascript'>top.location='".links('login')."';</script>");
                }else{
                    get_json('您已登陆超时!!!');
                }
            }
        } 
    }
}