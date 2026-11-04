<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Hits extends Mccms_Controller {

	public function __construct(){
		parent::__construct();
	}

	//漫画人气增加
    public function comic($id='') {
        $id = (int)$id;
        if($id == 0) exit;
        //清空月人气
        $month = file_get_contents(FCPATH."caches/month.txt");
        if($month != date('m')){
            $this->db->query("update ".Mc_SqlPrefix."comic set yhits=0");
            write_file(FCPATH."caches/month.txt",date('m'));
        }
        //清空周人气
        $week = file_get_contents(FCPATH."caches/week.txt");
        if($week != date('W',time())){
            $this->db->query("update ".Mc_SqlPrefix."comic set zhits=0");
            write_file(FCPATH."caches/week.txt",date('W',time()));
        }
        //清空日人气
        $day = file_get_contents(FCPATH."caches/day.txt");
        if($day != date('d')){
            $this->db->query("update ".Mc_SqlPrefix."comic set rhits=0");
            write_file(FCPATH."caches/day.txt",date('d'));
        }
        //增加人气
        $this->db->query("update ".Mc_SqlPrefix."comic set hits=hits+1,yhits=yhits+1,zhits=zhits+1,rhits=rhits+1 where id=".$id."");
	}

    //小说人气增加
    public function book($id='') {
        $id = (int)$id;
        if($id == 0) exit;
        //清空月人气
        $month = file_get_contents(FCPATH."caches/book_month.txt");
        if($month != date('m')){
            $this->db->query("update ".Mc_SqlPrefix."book set yhits=0");
            write_file(FCPATH."caches/book_month.txt",date('m'));
        }
        //清空周人气
        $week = file_get_contents(FCPATH."caches/book_week.txt");
        if($week != date('W',time())){
            $this->db->query("update ".Mc_SqlPrefix."book set zhits=0");
            write_file(FCPATH."caches/book_week.txt",date('W',time()));
        }
        //清空日人气
        $day = file_get_contents(FCPATH."caches/book_day.txt");
        if($day != date('d')){
            $this->db->query("update ".Mc_SqlPrefix."book set rhits=0");
            write_file(FCPATH."caches/book_day.txt",date('d'));
        }
        //增加人气
        $this->db->query("update ".Mc_SqlPrefix."book set hits=hits+1,yhits=yhits+1,zhits=zhits+1,rhits=rhits+1 where id=".$id."");
    }
}