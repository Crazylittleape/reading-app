<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Qpic extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}
  
    public function img() {
        $str = $this->input->get('str');
		$url = sys_auth($str,1);
		$data = getcurl($url);
		header("Content-Type:image/jpeg");
        echo $data;
	}
}