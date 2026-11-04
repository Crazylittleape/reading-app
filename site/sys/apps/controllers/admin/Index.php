<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends Mccms_Controller {
	function __construct(){
	    parent::__construct();
		//判断是否登陆
		$this->admin->login();
		if(SELF == 'admin.php') exit('请修改 ./admin.php后台入口文件名，防止暴露攻击~！');
	}

	public function index()
	{
		$data['nav'] = require FCPATH.'sys/libs/nav.php';
		$this->load->view('index/index.tpl',$data);
	}

	public function main()
	{
		//当日时间戳
		$jtime = strtotime(date('Y-m-d 0:0:0'))-1;
		//点击量
		$data['rhits'] = $this->mcdb->get_sum('comic','rhits');
		$data['hits'] = $this->mcdb->get_sum('comic','hits');
		//点击量
		$data['brhits'] = $this->mcdb->get_sum('book','rhits');
		$data['bhits'] = $this->mcdb->get_sum('book','hits');
		//充值额度
		$data['rmb'] = $this->mcdb->get_sum('order','rmb',array('addtime>'=>$jtime));
		$data['rmb2'] = $this->mcdb->get_sum('order','rmb',array('pid'=>1,'addtime>'=>$jtime));
		//订单数
		$dd = $this->mcdb->get_nums('order',array('addtime>'=>$jtime));
		$dd2 = $this->mcdb->get_nums('order',array('addtime>'=>$jtime,'pid'=>1));
		$data['dd'] = $dd;
		$data['bi'] = $dd2 == 0 ? 0 : round($dd2/$dd*100,2);
		//新增用户
		$data['u1'] = $this->mcdb->get_nums('user',array('addtime>'=>$jtime));
		$data['u2'] = $this->mcdb->get_nums('user');
		//APP用户
		$data['app1'] = $this->mcdb->get_nums('user_app');
		$app = $this->mcdb->get_row_arr('user_app_nums','*',array('date'=>date('Ymd')));
		$data['app2'] = $app ? $app['ios_nums']+$app['android_nums'] : 0;

		$config = array();$t = time();
		$data['config'] = '{}';
		$this->load->view('index/main.tpl',$data);
	}

	public function caches()
	{
		$res = $this->caches->clean();
		get_json('缓存更新完成...',1);
	}
}

