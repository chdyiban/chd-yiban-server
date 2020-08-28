<?php

namespace app\api\controller\dormitory2020;

use app\api\controller\dormitory2020\Api;
use think\Config;
use fast\Http;
use app\common\library\Token;
use fast\Random;
use think\Hook;
use think\Cache;
use think\Log;
use app\common\library\Sms as Smslib;
use app\api\model\dormitory2020\User as userModel;
use app\api\model\dormitory2020\Wxuser as wxUserModel;

/**
 * 微信订阅号授权接口
 */
class User extends Api
{


    //用来记录不需要绑定门户的接口
    protected $noNeedBindPortal = ['index','login'];

    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $AppSecretKey = "0paIib2iL0L6tirAVigty0Q";

    /**
     * @param code
     * @return array
     */
    public function index()
    {
        //解析后应对签名参数进行验证
        $code = $this->request->param("code");
        if (empty($code)) {
            $this->error("param error!");
        }
        $wxUserModel = new wxUserModel;
        $userModel = new userModel;
        $returnData = $wxUserModel->initStep($code);
        if ($returnData["status"] == false) {
            $this->error($returnData["msg"]);
        }
        if ($returnData["data"]["is_bind"] == false) {
            $this->success(__('Please Bind Portal Account First'), $returnData["data"]);
        } else {
            // 派发token
            $userid = $userModel->where("XH",$returnData["data"]["wxuser"]["portal_id"])->field("ID")->find()["ID"];
            unset($returnData["data"]["wxuser"]["portal_id"]);
            $token_old = Cache::get("dormitory_user_$userid");
            if ($token_old) {
                $info = Token::delete($token_old);
            }
            $this->_token = Random::uuid();
            Token::set($this->_token, $userid, $this->keeptime);
            Cache::set("dormitory_user_$userid",$this->_token,$this->keeptime);
            Hook::listen("user_login_successed", $userid);
            //返回派发的token
            $returnData["data"]["token"] = $this->_token;
        }
        // $userInfo = $this-> me();
        // $returnData["user_portal_info"] = $userInfo["data"]["user_info"];
        $returnData = $returnData["data"];
        $this->success("success",$returnData);
    }
     /**
     * 获取用户个人信息
     * @param string XH
     */

    public function me()
    {
        $param = $this->_user;
        $userModel = new userModel();
        $data = $userModel->getMeInfo($param);
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
        $userModel = new userModel();
        $data = $userModel->avatar($key,$this->_user["ID"]);
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
        $userModel = new userModel();
        if (empty($key['qq'])) {
            $this->error("请输入正确的QQ号!");
        }
        $qqInfo = parent::validate($key,'Userinfo.qq');
        if (gettype($qqInfo) == 'string') {
            $this->error($qqInfo);
        } 
        $data = $userModel->qq($key,$this->_user["ID"]);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }
    
     /**
     * 绑定接口 派发token
     * @param string $key["studentID"]
     * @param string $key["password"]
     * @param string $key["open_id]
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
        $wxUserModel = new wxUserModel();
        $userid = $wxUserModel->check($key);
        if($userid > 0){
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
                "bind"  => true,
                "token"  => $this->_token,
                "expired"=> $this->keeptime,
            ];
            $this->success("绑定成功",$data);
        } else if ($userid == -1) {
            $data = [
                "bind"  => false,
            ];
            $this->error("请勿重复绑定",$data);
        }else {
            $data = [
                "bind"  => false,
            ];
            $this->error("账号密码错误",$data);
        }
    }

    /**
     * 用户取消绑定门户信息
     */
    public function logout()
    {
        //设置登录标识
        $this->_logined = FALSE;
        //删除Token
        Token::delete($this->_token);
        $wxUserModel = new wxUserModel;
        $userInfo = $this->_user;
        $result = $wxUserModel->bindCancel($userInfo);
        if ($result["status"] == true) {
            $this->success($result["msg"],["is_bind" => false]);
        }
        $this->error($result["msg"]);
    }

    /**
     * 用户绑定手机时发送验证码
     */
    public function getVerifyCode()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        if (empty($key["mobile"])) {
            $this->error("params error!");
        }
        $res = $this->send($key);
        if ($res["status"]) {
            $this->success($res["msg"]);
        }
        $this->error($res["msg"]);
    }

    /**
     * 用户绑定手机号，附带验证码
     */

    public function setVerifyCode()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        // $key = $this->request->param();
        if (empty($key["verify_code"])) {
            $this->error("params error!");
        }
        $ret = $this->checkMobileCaptcha($key);
        if ($ret["status"] == true){
            $userModel = new userModel;
            $userInfo = $this->_user;
            $userInfo["mobile"]  =   $ret["data"]["mobile"];
            $result = $userModel->bindMobile($userInfo);
            if (!empty($result)) {
                $this->success("绑定成功");
            }
            $this->error("绑定失败，请稍后再试");
        }
        $this->error("验证码不正确");
    }

    /**
     * 发送验证码方法
     * @param array $key["mobile"]
     * @return array 
     */
    private function send($key)
    {
        $mobile = $key["mobile"];
        $wxUserModel = new wxUserModel;
        $check = $wxUserModel->checkMobile($key);
        $userId = $this->_user["id"];

        $cacheData = Cache::get("mobile_bind_$userId");
        if (!empty($cacheData) && time() - $cacheData['createtime'] < 60)
        {
            return ["status" => false, "msg" =>  "发送过于频繁，请稍后再试"];
        }
        if ($check == true) {
            return ["status" => false, "msg" =>  "该手机号已被使用！"];
        }
        $verify_code = mt_rand(1000, 9999);
        $msg = "[长大易班]验证码：".$verify_code."（10分钟内有效）。如非本人操作，请忽略本条短信。";
        $res = Smslib::notice($mobile, $msg);
        //放入缓存的参数
        $cacheArray = [
            "mobile"        =>  $mobile,
            "verify_code"   =>  $verify_code,
            "createtime"    =>  time(),
        ];
        Cache::set("mobile_bind_$userId",$cacheArray,600);
        return ["status" => true, "msg" => "短信已发送，请查收。"];
    }

    /**
     * 核对用户绑定手机验证码
     * @param $key
     * @return array
     */
    private function checkMobileCaptcha($key)
    {
        $verify_code = $key["verify_code"];
        $userId = $this->_user["id"];
        $cacheArray = Cache::get("mobile_bind_$userId");
        if ($verify_code == $cacheArray["verify_code"]) {
            return ["status" => true, "msg" => "验证成功！","data" => ["mobile" => $cacheArray["mobile"] ]];
        }
        return ["status" => false, "msg" => "验证码错误！"];
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
            "Ticket" => $params["ticket"],
            "Randstr" => $params["randstr"],
            "UserIP" =>  $params["userip"]
        ];
        $params = http_build_query($params);
        $response = Http::get($getUrl,$params);
        $result = json_decode($response,true);
        if ($result["response"] == 1) {
            return ["status" => true,"msg"=>"验证成功","data" => null];
        } else {
            return ["status" => false,"msg"=> $result["err_msg"],"data" => null];
        }
    }



}