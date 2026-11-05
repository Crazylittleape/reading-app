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
    /**
     * Strict check for new admin endpoints: re-reads the account on every
     * request, rejects disabled (sid != 0) or deleted administrators, and
     * enforces the qx permission string server-side. Returns the admin row.
     */
    function operator($json = false, $perm = ''){
        $id = intval($this->cookie->get('admin_id'));
        $login = (string)$this->cookie->get('admin_login');
        $admin = false;
        if($id > 0 && $login !== ''){
            $row = $this->mcdb->get_row('admin','id,name,pass,nichen,sid,qx',array('id'=>$id));
            if($row && intval($row->sid) === 0 && hash_equals(md5($id.$row->name.$row->pass.Admin_Code), $login)){
                $admin = $row;
            }
        }
        if(!$admin){
            $this->cookie->set('admin_id');
            $this->cookie->set('admin_nichen');
            $this->cookie->set('admin_login');
            $this->deny($json, 401, '您已登陆超时!!!', "<script language='javascript'>top.location='".links('login')."';</script>");
        }
        if($perm !== '' && !empty($admin->qx) && strpos($admin->qx, $perm) === false){
            $this->deny($json, 403, '没有操作权限', '没有操作权限');
        }
        return $admin;
    }

    private function deny($json, $status, $msg, $html){
        http_response_code($status);
        header('Cache-Control: no-store');
        if($json){
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode(array('code'=>-1,'msg'=>$msg), JSON_UNESCAPED_UNICODE));
        }
        header('Content-Type: text/html; charset=utf-8');
        die($html);
    }
}