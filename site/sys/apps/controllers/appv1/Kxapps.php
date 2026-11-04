<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Kxapps extends Mccms_Controller {

    public function __construct(){
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        //加载函数
        $this->load->helper('app_helper');
        //用户ID
        $this->uid = (int)$this->input->get_post('user_id');
        //用户token
        $this->token = $this->input->get_post('user_token');
    }
    
    //获取标签
    public function type() {
        //判断签名
        get_app_sign();
        $size = (int)$this->input->get_post('size');
        if($size == 0 || $size > 300) $size = 100;
        $d['code'] = 1;
        $d['list'] = $this->mcdb->get_select('type','id,name',array('fid'=>1),'xid ASC',$size);
        get_json($d);
    }
	
	 //评论列表
    public function comment() {
        //判断签名
        get_app_sign();
        //查询条件
        $wh = array('uid'=>$this->uid);
		//偏移量
		$limit = array(999);
        //评论列表
		$comment = $this->mcdb->get_select('comment','mid,id,text,uid,reply_num,zan,addtime',$wh,'id DESC',$limit);
		foreach($comment as $k=>$v){
		    $rowu = $this->mcdb->get_row_arr('user','name,nichen,pic',array('id'=>$v['uid']));
		    $comment[$k]['unichen'] = empty($rowu['nichen']) ? $rowu['name'] : $rowu['nichen'];
            $comment[$k]['upic'] = getpic($rowu['pic'],'user');
            $comment[$k]['addtime'] = date('m-d H:i',$v['addtime']);
			$comment[$k]['comic'] = $this->mcdb->get_row_arr('comic','*',array('id'=>$v['mid']));
            //是否赞过
            $comment[$k]['is_zan'] = 0;
            if($this->uid > 0){
                $rowz = $this->mcdb->get_row_arr('comment_zan','id',array('fid'=>0,'cid'=>$v['id'],'uid'=>$this->uid));
                if($rowz) $comment[$k]['is_zan'] = 1;
            }
		}
		//输出
		$data['list'] = get_app_data($comment);
		get_json($data,1);
    }
	
	//APP公告通知
    public function notice() {
        $app = require FCPATH.'sys/libs/app.php';
        //输出
        $d['mode'] = $app['Kx_Notice_Mode'];
        $d['text'] = html_entity_decode($app['Kx_Notice'],ENT_QUOTES,'UTF-8');
        get_json($d,1);
    }
    
}