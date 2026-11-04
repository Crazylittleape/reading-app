<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
		header("Access-Control-Allow-Origin: *");
		//加载函数
		$this->load->helper('app_helper');
		//判断签名
		get_app_sign();
		//用户ID
		$this->uid = (int)$this->input->get_post('user_id');
	}
	
	//邮箱登录
	public function index() {
		$email = safe_replace($this->input->get_post('email',true));
		$pass = $this->input->get_post('pass',true);
		if(empty($email) || empty($pass)) get_json('账号密码不能为空',0);
		
        //判断账号是否存在
        $row = $this->mcdb->get_row_arr('user','*',array('email'=>$email));
        if(!$row) $row = $this->mcdb->get_row_arr('user','*',array('name'=>$email));
        if(!$row) get_json('邮箱不存在，请注册~',0);
        if($row['sid'] == 1) get_json('账户已被锁定~',0);
        if($row['pass_err'] > 10) get_json('密码错误次数太多，请找回密码~',0);
        if(md5($pass) != $row['pass']){
            if($row['pass_err'] > 2){
                get_json('密码不正确，可以下方找回密码~',0);
            }else{
                $this->mcdb->get_update('user',$row['id'],array('pass_err'=>($row['pass_err']+1)));
                get_json('密码不正确~',0);
            }
        }
        $edit = array();
        if($row['vip'] > 0 && $row['viptime'] < time()) $edit['vip'] = 0;
        $edit['pass_err'] = 0;
        $this->mcdb->get_update('user',$row['id'],$edit);

        //输出
        $d['code'] = 1;
        $d['msg'] = '登陆成功~';
        $d['uid'] = $row['id'];
        $d['token'] = md5('mccms_app'.$row['id'].$row['email'].$row['pass'].Mc_Encryption_Key);
        get_json($d);
	}

	//登录
// 	public function index() {
// 		$tel = safe_replace($this->input->get_post('tel',true));
// 		$pass = $this->input->get_post('pass',true);
// 		if(empty($tel) || empty($pass)) get_json('账号密码不能为空',0);
		
//         //判断账号是否存在
//         $row = $this->mcdb->get_row_arr('user','*',array('tel'=>$tel));
//         if(!$row) $row = $this->mcdb->get_row_arr('user','*',array('name'=>$tel));
//         if(!$row) get_json('手机不存在，请注册~',0);
//         if($row['sid'] == 1) get_json('账户已被锁定~',0);
//         if($row['pass_err'] > 10) get_json('密码错误次数太多，请找回密码~',0);
//         if(md5($pass) != $row['pass']){
//             if($row['pass_err'] > 2){
//                 get_json('密码不正确，可以下方找回密码~',0);
//             }else{
//                 $this->mcdb->get_update('user',$row['id'],array('pass_err'=>($row['pass_err']+1)));
//                 get_json('密码不正确~',0);
//             }
//         }
//         $edit = array();
//         if($row['vip'] > 0 && $row['viptime'] < time()) $edit['vip'] = 0;
//         $edit['pass_err'] = 0;
//         $this->mcdb->get_update('user',$row['id'],$edit);

//         //输出
//         $d['code'] = 1;
//         $d['msg'] = '登陆成功~';
//         $d['uid'] = $row['id'];
//         $d['token'] = md5('mccms_app'.$row['id'].$row['tel'].$row['pass'].Mc_Encryption_Key);
//         get_json($d);
// 	}

    //邮箱注册
	public function reg() {
		$email = safe_replace($this->input->get_post('email',true));
		$pass = $this->input->get_post('pass',true);
		$code = (int)$this->input->get_post('code');
		//邀请码
		$inviteid = (int)$this->input->get_post('inviteid')-10000;
        //设备id
        $deviceid = $this->input->get_post('deviceid',true);
// 		if(!is_tel($tel)) get_json('请输入正确的邮箱地址',0);
		if(empty($pass)) get_json('请输入登录密码',0);
		if(User_Reg_Tel == 0){
            if(empty($code)) get_json('请输入邮箱验证码',0);
            //判断手机验证码是否正确
            $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$email));
            if(!$row || $row['code'] != $code) get_json('邮箱验证码错误',0);
        }
        //判断账号是否存在
        $row = $this->mcdb->get_row_arr('user','*',array('email'=>$email));
        if($row) get_json('该邮箱已注册',0);
        
        //注册
        $add['addtime'] = time();
        $add['name'] = 'T-'.substr(md5($add['addtime']),8,-8);
        $add['sid'] = 0;
        $add['email'] = $email;
        $add['pass'] = md5($pass);
        $add['cion'] = User_Reg_Cion;
        $add['vip'] = User_Reg_Vip;
        $add['viptime'] = 0;
        if(User_Reg_Vip_Day > 0) $add['viptime'] = time()+86400*User_Reg_Vip_Day;
        $res = $this->mcdb->get_insert('user',$add);
        if(!$res) get_json('注册失败',0);
        
        //删除手机验证码记录
        $this->mcdb->get_del('telcode',$email,'tel');
        
        //判断邀请码
        if($inviteid > 0){
            $rowu = $this->mcdb->get_row_arr('user','id',array('id'=>$inviteid));
            if($rowu){
                //判断设备ID是否存在
                $row2 = $this->mcdb->get_row_arr('user_invite','id',array('deviceid'=>$deviceid));
                if(!$row2){
                    $this->mcdb->get_insert('user_invite',array('uid'=>$res,'inviteid'=>$inviteid,'deviceid'=>$deviceid,'addtime'=>time()));
                    //领取奖励
                    $user = $this->mcdb->get_row_arr('user','id,vip,viptime',array('id'=>$inviteid));
                    app_task_reward($this->mcdb,2,$user);
                }
            }
        }
        
        //输出
        $d['code'] = 1;
        $d['msg'] = '注册成功';
        $d['uid'] = $res;
        $d['token'] = md5('mccms_app'.$res.$email.md5($pass).Mc_Encryption_Key);
        get_json($d);
	}

	//注册
// 	public function reg() {
// 		$tel = safe_replace($this->input->get_post('tel',true));
// 		$pass = $this->input->get_post('pass',true);
// 		$code = (int)$this->input->get_post('code');
// 		//邀请码
// 		$inviteid = (int)$this->input->get_post('inviteid')-10000;
//         //设备id
//         $deviceid = $this->input->get_post('deviceid',true);
// 		if(!is_tel($tel)) get_json('请输入正确的手机号',0);
// 		if(empty($pass)) get_json('请输入登录密码',0);
// 		if(User_Reg_Tel == 0){
//             if(empty($code)) get_json('请输入手机验证码',0);
//             //判断手机验证码是否正确
//             $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$tel));
//             if(!$row || $row['code'] != $code) get_json('手机验证码错误',0);
//         }
//         //判断账号是否存在
//         $row = $this->mcdb->get_row_arr('user','*',array('tel'=>$tel));
//         if($row) get_json('该手机已注册',0);
        
//         //注册
//         $add['addtime'] = time();
//         $add['name'] = 'T-'.substr(md5($add['addtime']),8,-8);
//         $add['sid'] = 0;
//         $add['tel'] = $tel;
//         $add['pass'] = md5($pass);
//         $add['cion'] = User_Reg_Cion;
//         $add['vip'] = User_Reg_Vip;
//         $add['viptime'] = 0;
//         if(User_Reg_Vip_Day > 0) $add['viptime'] = time()+86400*User_Reg_Vip_Day;
//         $res = $this->mcdb->get_insert('user',$add);
//         if(!$res) get_json('注册失败',0);
        
//         //删除手机验证码记录
//         $this->mcdb->get_del('telcode',$tel,'tel');
        
//         //判断邀请码
//         if($inviteid > 0){
//             $rowu = $this->mcdb->get_row_arr('user','id',array('id'=>$inviteid));
//             if($rowu){
//                 //判断设备ID是否存在
//                 $row2 = $this->mcdb->get_row_arr('user_invite','id',array('deviceid'=>$deviceid));
//                 if(!$row2){
//                     $this->mcdb->get_insert('user_invite',array('uid'=>$res,'inviteid'=>$inviteid,'deviceid'=>$deviceid,'addtime'=>time()));
//                     //领取奖励
//                     $user = $this->mcdb->get_row_arr('user','id,vip,viptime',array('id'=>$inviteid));
//                     app_task_reward($this->mcdb,2,$user);
//                 }
//             }
//         }
        
//         //输出
//         $d['code'] = 1;
//         $d['msg'] = '注册成功';
//         $d['uid'] = $res;
//         $d['token'] = md5('mccms_app'.$res.$tel.md5($pass).Mc_Encryption_Key);
//         get_json($d);
// 	}

    //找回密码
	public function pass() {
	    $email = safe_replace($this->input->get_post('email',true));
		$pass = $this->input->get_post('pass',true);
		$code = (int)$this->input->get_post('code');
// 		if(!is_tel($tel)) get_json('请输入正确的手机号',0);
		if(empty($pass)) get_json('请输入登录密码',0);
        if(empty($code)) get_json('请输入手机验证码',0);
        //判断手机验证码是否正确
        $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$email));
        if(!$row || $row['code'] != $code) get_json('邮箱验证码错误',0);

        //判断账号是否存在
        $row = $this->mcdb->get_row_arr('user','*',array('email'=>$email));
        if(!$row) get_json('该邮箱未注册',0);
        //修改密码
        $this->mcdb->get_update('user',$row['id'],array('pass'=>md5($pass)));
        
        //输出
        $d['code'] = 1;
        $d['msg'] = '密码修改成功';
        $d['uid'] = $row['id'];
        $d['token'] = md5('mccms_app'.$row['id'].$email.md5($pass).Mc_Encryption_Key);
        get_json($d);
	}
	
	//修改密码
	public function pass_edit() {
	    $tel = safe_replace($this->input->get_post('tel',true));
		$pass = $this->input->get_post('pass',true);
		$code = (int)$this->input->get_post('code');
		if(!is_tel($tel)) get_json('请输入正确的手机号',0);
		if(empty($pass)) get_json('请输入登录密码',0);
        if(empty($code)) get_json('请输入手机验证码',0);
        //判断手机验证码是否正确
        $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$tel));
        if(!$row || $row['code'] != $code) get_json('手机验证码错误',0);

        //判断账号是否存在
        $row = $this->mcdb->get_row_arr('user','*',array('tel'=>$tel));
        if(!$row) get_json('该手机未注册',0);
        //修改密码
        $this->mcdb->get_update('user',$row['id'],array('pass'=>md5($pass)));
        
        //输出
        $d['code'] = 1;
        $d['msg'] = '密码修改成功';
        $d['uid'] = $row['id'];
        $d['token'] = md5('mccms_app'.$row['id'].$tel.md5($pass).Mc_Encryption_Key);
        get_json($d);
	}
	
	//发送邮箱验证码
    public function emailcode($op='') {
        $email = $this->input->get_post('email',true);
        //修改手机获取原手机
        if($op == 'edit') $email = getzd('user','email',$this->uid);
        // if(!is_tel($tel)) get_json('手机号码格式错误',0);
        //判断手机是否注册
        if($op == 'reg'){
            $type_name = '注册账号';
            $reg = $this->mcdb->get_row_arr('user','id',array('email'=>$email));
            if($reg) get_json('该邮箱已注册',0);
        }
        //判断手机是否存在
        if($op == 'pass'){
            $type_name = '找回密码';
            $reg = $this->mcdb->get_row_arr('user','id',array('email'=>$email));
            if(!$reg) get_json('该邮箱未注册',0);
        }
        //判断发送时间
        $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$email));
        if($row){
            if($row['addtime']+60 > time()) get_json('操作太频繁',0);
        }
        //验证码
        $tcode = rand(111111,999999);
        //发送邮箱验证码
        $email_data = array(
			'type' => Mail_Type,
			'host' => Mail_Host,
			'port' => Mail_Port,
			'user' => Mail_User,
			'pass' => Mail_Pass,
			'crypto' => Mail_Crypto,
			'form_mail' => Mail_User,
			'form_name' => Mail_Name,
			'to_mail' => $email,
			'title' => str_replace("&#123;site_name&#125;",Web_Name,Mail_Code_Title),
			'html' => str_replace("&#123;code&#125;",$tcode,str_replace("&#123;type&#125;",$type_name,str_replace("&#123;web_url&#125;",Web_Url,Mail_Code_Msg))),
		);
        //发送邮箱验证码
        $this->load->model('mail');
        $res = $this->mail->send($email_data);
        // $this->load->library('sms');
        // $res = $this->sms->add($tel,$tcode);
        if($res){
            //操作数据库
            if($row){
                $this->mcdb->get_update('telcode',$row['id'],array('code'=>$tcode,'addtime'=>time()));
            }else{
                $this->mcdb->get_insert('telcode',array('tel'=>$email,'code'=>$tcode,'addtime'=>time()));
            }
            get_json('验证码发送成功',1);
        }else{
            get_json('发送失败，稍后再试',0);
        }
    }
	
    //发送手机验证码
    public function telcode($op='') {
        $tel = $this->input->get_post('tel',true);
        //修改手机获取原手机
        if($op == 'edit') $tel = getzd('user','tel',$this->uid);
        if(!is_tel($tel)) get_json('手机号码格式错误',0);
        //判断手机是否注册
        if($op == 'reg'){
            $reg = $this->mcdb->get_row_arr('user','id',array('tel'=>$tel));
            if($reg) get_json('该手机已注册',0);
        }
        //判断手机是否存在
        if($op == 'pass'){
            $reg = $this->mcdb->get_row_arr('user','id',array('tel'=>$tel));
            if(!$reg) get_json('该手机未注册',0);
        }
        //判断发送时间
        $row = $this->mcdb->get_row_arr('telcode','*',array('tel'=>$tel));
        if($row){
            if($row['addtime']+60 > time()) get_json('操作太频繁',0);
        }
        //验证码
        $tcode = rand(111111,999999);
        //发送手机验证码
        $this->load->library('sms');
        $res = $this->sms->add($tel,$tcode);
        if($res){
            //操作数据库
            if($row){
                $this->mcdb->get_update('telcode',$row['id'],array('code'=>$tcode,'addtime'=>time()));
            }else{
                $this->mcdb->get_insert('telcode',array('tel'=>$tel,'code'=>$tcode,'addtime'=>time()));
            }
            get_json('验证码发送成功',1);
        }else{
            get_json('发送失败，稍后再试',0);
        }
    }
}