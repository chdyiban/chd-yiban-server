<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Form as FormModel;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
/**
 * 万能表单
 */
class Form extends Api
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
        $FormModel = new FormModel;
        $result = $FormModel -> initForm($key);
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } 
        $this->error($result["msg"],$result["data"]);       
    }
    /**
     * 获取表单详情
     * @param token
     * @param id
     * @type   加密
     */
    public function detail()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);

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

        if (empty($key["id"])) {
            $this->error("params error");
        } else {
            $FormModel = new FormModel;
            $result = $FormModel -> detail($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
            } 
            $this->error($result["msg"],$result["data"]);
        }
    }

    /**
     * 提交问卷答案
     * @param token
     * @param data
     * @type 加密
     */
    public function submit(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
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

        if (empty($userInfo["portal_id"]) || empty($key["data"]) || empty($key['openid'])) {
            $this->error("params error");
        } else {
            $FormModel = new FormModel;
            $result = $FormModel -> submit($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
            }
            $this->error($result["msg"],$result["data"]);
        }
    }
}