<?php

namespace app\api\controller\dormitory2019;

use app\api\controller\dormitory2019\Api;
use app\common\library\Token;
use think\Cache;
use app\api\model\dormitory\User as UserModel;
use fast\Random;
use think\Hook;
use fast\Http;


/**
 * 选房会员登录系统，另一套派发，所以原来的权限和是否需要登录完全失效
 */
class User extends Api
{
    protected $noNeedLogin = ['login','captcha'];

    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $AppSecretKey = "0paIib2iL0L6tirAVigty0Q";

    /**
     * 登录接口
     * @param string $key["studentID"]
     * @param string $key["password"]
     * @param array  $key["verify"]
     * 35ms-60ms
     */
    public function login(){
        header('Access-Control-Allow-Origin:*');  
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        if (empty($key["verify"])) {
            $this->error("params error!");
        }
        $key["verify"]["userip"] =  $this->request->ip();
        $response = $this->captcha($key["verify"]);

        if (!$response["status"]) {
            $this->error($response["msg"]);
        }
        $User = new UserModel();
        $userid = $User->check($key);
        if($userid){
            $token_old = Cache::get("dormitory_user_$userid");
            if ($token_old) {
                $info = Token::delete($token_old);
            }
            $this->_token = Random::uuid();
            Token::set($this->_token, $userid, $this->keeptime);
            Cache::set("dormitory_user_$userid",$this->_token,$this->keeptime);
            Hook::listen("user_login_successed", $userid);
            //判断是否是否信息填完并且是否需要修改
            $data = [
                "login"  => true,
                "token"  => $this->_token,
                "expired"=> $this->keeptime,
            ];
            $this->success("",$data);
        } else {
            $data = [
                "login"  => false,
            ];
            $this->error("账号密码错误",$data);
        }
    }


    /**
     * 腾讯验证码后台接入
     * @param $param["appid"]
     * @param $param["AppSecretKey"]
     * @param $param["Ticket"]
     * @param $param["randstr"]
     */

    private function captcha($params)
    {
        $getUrl = "https://ssl.captcha.qq.com/ticket/verify";
        if (empty($params["appid"]) || empty($params["ticket"]) || empty($params["randstr"])) {
            return ["status" => false,"msg"=>"params error!","data" => null];
        }
        $params = [
            "aid" => $params["appid"],
            "AppSecretKey" => $this->AppSecretKey,
            // "AppSecretKey" => "0paIib2iL0L6tirAVigty0Q**",

            "Ticket" => $params["ticket"],
            "Randstr" => $params["randstr"],
            "UserIP" =>  $params["userip"]
        ];
        // dump($params);
        $params = http_build_query($params);
        $response = Http::get($getUrl,$params);
        $result = json_decode($response,true);
        if ($result["response"] == 1) {
            return ["status" => true,"msg"=>"验证成功","data" => null];
        } else {
            return ["status" => false,"msg"=> $result["err_msg"],"data" => null];
        }
    }

 
    /**
     * 获取用户个人信息
     * @param string XH
     */

    public function me()
    {
        // $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        // $param = [
        //     "ID" => $this->_user["ID"],
        //     "XH" => $this->_user["XH"],
        //     "XQ" => $this->_user["XQ"],
        // ];
        $param = $this->_user;
        $UserModel = new UserModel();
        $data = $UserModel->getMeInfo($param);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }

    /**
     * 更换用户头像
     * @param string avatar
     */

    public function avatar()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $UserModel = new UserModel();
        $data = $UserModel->avatar($key,$this->_user["ID"]);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
    /**
     * 更换用户QQ
     * @param string QQ
     */

    public function qq()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $UserModel = new UserModel();
        if (empty($key['qq'])) {
            $this->error("请输入正确的QQ号!");
        }
        $qqInfo = parent::validate($key,'Userinfo.qq');
        if (gettype($qqInfo) == 'string') {
            $this->error($qqInfo);
        } 
        $data = $UserModel->qq($key,$this->_user["ID"]);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    // public function logout()
    // {
    //     $token = $this->request->param('token');
    //     $loginInfo = $this -> isLogin($token);
    //     $token_status = Token::has($token, $loginInfo['user_id']);
    //     if ($token_status) {
    //         $info = Token::rm($token);
            
    //     } else {
    //         $this->error('账号不存在');
    //     }
    // }

    /**
     * 注销
     * 
     * @return array
     */
    public function logout()
    {
        //设置登录标识
        $this->_logined = FALSE;
        //删除Token
        Token::delete($this->_token);
        //注销成功的事件
        $this->success('已退出');
    }

}