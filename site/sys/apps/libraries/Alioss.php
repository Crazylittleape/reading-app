<?php
/*
 
 
 
'--------------------------------------------------------
 
 
'--------------------------------------------------------
*/
if (!defined('BASEPATH')) exit('No direct script access allowed');

use OSS\OssClient;
use OSS\Core\OssException;
 
require_once MCCMSPATH.'class/aliyun_oss/autoload.php';
 
Class Alioss{
 
    public function __construct(){
        log_message('debug', "Native Alioss Class Initialized");
    }

    //上传
    public function upload($file_path){
        //获取对象
        $ossClient = new OssClient(Annex_Oss_Aid, Annex_Oss_Key, Annex_Oss_End);
        try {
            $oss_file_path = str_replace(FCPATH, '', $file_path);
            $result = $ossClient->uploadFile(Annex_Oss_Bucket, $oss_file_path, $file_path);
            //判断删除本地文件
            if(Annex_Pic_Del == 0) unlink($file_path);
            return $oss_file_path;
        } catch (OssException $e) {
            //exit($e->getMessage());
            unlink($file_path);
            return false;
        }
    }
 
    //删除
    public function del($file_path){
        //获取对象
        $ossClient = new OssClient(Annex_Oss_Aid, Annex_Oss_Key, Annex_Oss_End);
        try {
            $oss_file_path = str_replace(FCPATH, '', $file_path);
            return $ossClient->deleteObject(Annex_Oss_Bucket,$file_path);
        } catch (OssException $e) {
            //exit($e->getMessage());
            return false;
        }
    }
}