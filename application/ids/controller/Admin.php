<?php
namespace app\ids\controller;

use think\Db;
use think\Loader;
use think\Config;
use think\Session;
// use app\common\controller\Backend;
use think\Controller;
use app\admin\controller\Index as IndexController;
/**
 * cas认证登录后台
 */
class Admin extends IndexController
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index($type = null)
    {
        header("Content-Type: text/html; charset=utf-8");
        // session_start();
        Loader::import('CAS.phpCAS');
        $phpCAS = new \phpCAS();
        $phpCAS->client(CAS_VERSION_2_0,'ids.chd.edu.cn',80,'authserver',false);
        $phpCAS->setNoCasServerValidation();
        $phpCAS->handleLogoutRequests();
        $phpCAS->forceAuthentication(); 
        if ($type == "logout") {
            $param = array('service'=>'http://ids.chd.edu.cn/authserver/login?service=https%3A%2F%2Fyiban.chd.edu.cn%2Fids%2Fadmin%2Findex');
            $phpCAS->logout($param);
            // $url = $this->request->get('url', 'yibanht.php/index/index');
            // $this->success(__('退出成功'), $url);
        }
        $user = $phpCAS->getUser();
        // $user = $this->request->get("ID");
        if($user == ''){
            die('unkown error');
        }
        // 如果为老师
        // $indexController = new IndexController();
        if(strlen($user) == 6){
            $this->loginSchool($user);
        } else {
            //去教职工信息表中查询，如果没有则为学生
            $result = Db::name("teacher_detail")->where("ID",$user)->find();
            if (empty($result)) {
                $this->error("暂无使用权限");
            } 
            $this->loginSchool($user);
        }

    }


}






