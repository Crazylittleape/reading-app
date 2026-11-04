<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 文件缓存类
 */
class Caches {

    function __construct (){
		log_message('debug', "Native Cache Class Initialized");
		$this->ci = &get_instance();
		$this->_id = false;
		$this->_time = Cache_Time;
		if(Cache_Mode == 3){
			$this->_adapter = 'redis';
		}elseif(Cache_Mode == 2){
			$this->_adapter = 'memcached';
		}else{
			$this->_adapter = 'file';
		}
		$this->ci->load->driver('cache',array('adapter'=>$this->_adapter,'backup'=>'file','key_prefix'=>Cache_Rand));
	}

    //读取缓存
	function get($cacheid){
        if(defined('MOBILE')) $cacheid.='_wap';
		$this->_id = md5($cacheid);
		return $this->ci->cache->get($this->_id);
	}

    //写入缓存
	function save($data){
		//写缓存
		return $this->ci->cache->save($this->_id, $data, $this->_time);
	}

    //获取缓存
	function start($id,$time=0){
		//关闭缓存
		if(Cache_Mode == 0 || defined('IS_ADMIN')) return false;
		if($time > 0) $this->_time = $time;
		$data = $this->get($id);
		if($data !== false  && !empty($data)){
			exit($data);
			return true;
		}else{
			ob_start();
			ob_implicit_flush(true);
			return false;
		}
	}

	function end($data = ''){
		if(Cache_Mode > 0 && !defined('IS_ADMIN')){
		    $data = ob_get_contents();
		    ob_end_flush();
			$this->save($data);
		}
	}

    //删除缓存
	function del($cacheid){
        if(defined('MOBILE')) $cacheid.='_wap';
		$this->_id = md5($cacheid);
		return $this->ci->cache->delete($this->_id);
	}

    //清空整个缓存
	function clean(){
		return $this->ci->cache->clean();
	}
}