<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * URL推送类，支持百度、熊掌、神马
 */
class Push {
    public function __construct(){
		log_message('debug', "Native Push Class Initialized");
	}

	//获取推送完整地址
	public function add($url) {
		if(Push_Type == '') return false;
		$arr = explode('|',Push_Type);
		foreach ($arr as $type) {
			$this->send($url,$type);
		}
		return true;
	}

	//获取推送完整地址
	public function send($url,$type='bd') {
		if(!is_array($url)) $url = array($url);
		if(count($url) == 0) return false;
		$parr = parse_url($url[0]);
		$site = $parr['host'];
		if($type == 'bd'){
			if(Push_Bd_Token == '') return false;
			$api = 'http://data.zz.baidu.com/urls?site='.$site.'&token='.Push_Bd_Token;
		}elseif($type == 'xz'){
			if(Push_Xz_Appid == '' || Push_Xz_Token == '') return false;
			$api = 'http://data.zz.baidu.com/urls?appid='.Push_Xz_Appid.'&token='.Push_Xz_Token.'&type=realtime';
		}else{
			if(Push_Sm_User == '' || Push_Sm_Token == '') return false;
			$api = 'http://data.zhanzhang.sm.cn/push?site=pc6a.com&user_name='.Push_Sm_User.'&resource_name=mip_add&token='.Push_Sm_Token;
		}
		$res = $this->curl_post($api,$url);
		return $res;
	}

	//Curl提交推送
	private function curl_post($api,$urls = array()){
		$ch = curl_init();
		$options =  array(
		    CURLOPT_URL => $api,
		    CURLOPT_POST => true,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_POSTFIELDS => implode("\n", $urls),
		    CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
		);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		return $result;
	}
}