<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\AdminLog;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use think\Db;
use fast\Random;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;
use think\Cookie;
use app\ids\controller\Admin as AdminController;
/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login','loginschool'];
    protected $noNeedRight = ['index', 'logout','loginschool'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
            'auth/rule' => __('Menu'),
            'general'   => ['new', 'purple'],
        ], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $adminModel = new Admin();
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            // $logintype = $this->request->post('logintype');
            $keeplogin = $this->request->post('keeplogin');
            //辅导员职业能力大赛需要改写登录接口
            // if ($logintype == "normal") {
                $token = $this->request->post('__token__');
                $rule = [
                    'username'  => 'require|length:3,30',
                    'password'  => 'require|length:3,30',
                    '__token__' => 'token',
                ];
                $data = [
                    'username'  => $username,
                    'password'  => $password,
                    '__token__' => $token,
                ];
                if (Config::get('fastadmin.login_captcha')) {
                    $rule['captcha'] = 'require|captcha';
                    $data['captcha'] = $this->request->post('captcha');
                }
                $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
                $result = $validate->check($data);
                if (!$result) {
                    $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
                }
                AdminLog::setTitle(__('Login'));
                $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
                if ($result === true) {
                    Hook::listen("admin_login_after", $this->request);
                    Cookie::set("loginType","normal",86400);
                    $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
                } else {
                    $msg = $this->auth->getError();
                    $msg = $msg ? $msg : __('Username or password is incorrect');
                    $this->error($msg, $url, ['token' => $this->request->token()]);
                }
            //门户账号登录
            // } elseif ($logintype == "school") {
            //     $token = $this->request->post('__token__');
            //     $checkResult = $adminModel->check($username,$password);
            //     $checkAdmin  = Db::name("admin") -> where("username",$username) -> find();
            //     //如果已经注册，那么直接登录
            //     if (!empty($checkAdmin)) {
            //         $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);                    
            //         if ($result === true) {
            //             Hook::listen("admin_login_after", $this->request);
            //             $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            //         } else {
            //             $msg = $this->auth->getError();
            //             $msg = $msg ? $msg : __('Username or password is incorrect');
            //             $this->error($msg, $url, ['token' => $this->request->token()]);
            //         }
            //     }
            //     //如果没有注册那么就先注册
            //     //门户账号密码正确
            //     if ($checkResult === true) {
            //         //在数据中获取导员基本信息
            //         $basicInfo = $this->getBasicInfo($username);
            //         if ($basicInfo["status"] == false) {
            //             $this->error("未查找到相关信息，请联系管理员");
            //         }
            //         $paramsRegister = [
            //             "password"  => $password,
            //             "username"  => $username,
            //             "nickname"  => $basicInfo["data"]["XM"],
            //             "email"     => $username."@QQ.com",
            //         ];
            //         // dump($paramsRegister);
            //         $ret = $this->register($paramsRegister);
                    
            //         if ($ret["code"] != 0) {
            //             $this->error($ret["msg"]);
            //         }

            //         $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            //         if ($result === true) {
            //             Hook::listen("admin_login_after", $this->request);
            //             $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            //         } else {
            //             $msg = $this->auth->getError();
            //             $msg = $msg ? $msg : __('Username or password is incorrect');
            //             $this->error($msg, $url, ['token' => $this->request->token()]);
            //         }
            //     } else {
            //         $msg = "账号密码有误，请检查";
            //         $this->error($msg, $url, ['token' => $this->request->token()]);
            //     }
               
            // }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background;
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * cas调用登录方法 
     */
    public function loginSchool($username)
    {
        $url = $this->request->get('url', 'yibanht.php/index/index');
        $password  = "";
        $keeplogin = 86400;
        // $token = $this->request->post('__token__');
        // $checkResult = $adminModel->check($username,$password);
        $checkAdmin  = Db::name("admin") -> where("username",$username) -> find();
        //如果已经注册，那么直接登录
        if (!empty($checkAdmin)) {
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0,"school");                    
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('登录成功'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                // $this->error($msg, $url, ['token' => $this->request->token()]);
                $this->error($msg, $url);
            }
        }
        //如果没有注册那么就先注册
        //在数据中获取导员基本信息
        $basicInfo = $this->getBasicInfo($username);
        if ($basicInfo["status"] == false) {
            $this->error("未查找到相关信息，请联系管理员");
        }
        $paramsRegister = [
            "password"  => "",
            "username"  => $username,
            "nickname"  => $basicInfo["data"]["XM"],
            "email"     => $username."@QQ.com",
        ];
        // dump($paramsRegister);
        $ret = $this->register($paramsRegister);
        
        if ($ret["code"] != 0) {
            $this->error($ret["msg"]);
        }

        $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0,"school");
        if ($result === true) {
            Hook::listen("admin_login_after", $this->request);
            $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
        } else {
            $msg = $this->auth->getError();
            $msg = $msg ? $msg : __('Username or password is incorrect');
            $this->error($msg, $url, ['token' => $this->request->token()]);
        }
          // 根据客户端的cookie,判断是否可以自动登录
          if ($this->auth->autologin()) {
            $this->redirect($url);
        }

    }

    /**
     * 注销登录
     */
    public function logout()
    {
        
        $loginType = Cookie::get('loginType');
        if ($loginType == "cas") {
            $adminController = new AdminController();
            $this->auth->logout();
            $adminController->index("logout");
        } else {
            $this->auth->logout();
            Hook::listen("admin_logout_after", $this->request);
        }
        Cookie::delete("loginType");
        $this->success(__('Logout successful'), 'index/login');
    }

    /**
     * 获取辅导员基本信息
     */

    public function getBasicInfo($username)
    {
        $info = Db::name("teacher_detail")
                -> where("ID",$username)
                -> field("XM,XBDM")
                -> find();
        if (!empty($info)) {
            return ["status" => true, "data" => $info];
        }
        return ["status" => false, "data" => ""];
    }

     /**
     * 注册管理员
     *
     * @param string username 用户名
     * @param string nickname 昵称
     * @param string password 密码
     * @param string email    邮箱
     * @param string mobile   手机号
     */
    public function register($params)
    {

        $adminModel = new Admin();
        $authGroupModel = new AuthGroup();
        $AuthGroupAccess = new AuthGroupAccess();
        // $params = $this->request->param();
   
        // $params['salt'] = Random::alnum();
        // $params['password'] = md5(md5($params['password']) . $params['salt']);
        $params["salt"] =   "";
        $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
        $params["status"] = "normal";
        $group = $authGroupModel->where("name","辅导员组")->find()["id"];
        //判断用户是否存在
        $checkInfo = Db::name("admin")->where("username",$params["username"])->find();
        if (!empty($checkInfo)) {
            $info = ["code" => 10,"msg" => "用户名重复"];
            return $info;
        }
        $result = $adminModel->save($params);
        if (!$result)
        {
            //$adminModel->getError()
            $info = ["code" => 10,"msg" => "用户名重复"];
            return $info;
        }

        $dataset = ['uid' => $adminModel->id, 'group_id' => $group];
        $AuthGroupAccess->save($dataset);
        $info = ["code" => 0,"msg" => "注册成功"];
        return $info;
    }

}
