<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Clock as ClockModel;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
/**
 * 打卡应用
 */
class Clock extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * @param token
     * @type 不加密
     */
    public function index()
    {
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        $key["openid"] = $userInfo["open_id"];
        // $key = ["openid" => "o5WD50I1ZhBv7aztZUsaPZRLE30Q","activity_id" => "1"];
        $ClockModel = new ClockModel;
        $result = $ClockModel -> index($key);
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } else {
            $this->error($result["msg"],$result["data"]);
        }
    }
    /**
     * 报名
     * @param token
     * @type 不加密
     */
    public function apply()
    {
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
      
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);

        $key["openid"] = $userInfo["open_id"];

        $ClockModel = new ClockModel;
        $result = $ClockModel -> apply($key);
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } else {
            $this->error($result["msg"],$result["data"]);
        }
    
    }

    /**
     * 打卡
     * @param token
     * @type 不加密
     */
    public function clock(){
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        $key["openid"] = $userInfo["open_id"];

        $ClockModel = new ClockModel;
        $result = $ClockModel -> clock($key);
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } else {
            $this->error($result["msg"],$result["data"]);
        }
    
    }
}