<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Adviser as AdviserModel;
use app\common\library\Token;
/**
 * 班主任评价
 */
class Adviser extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * @param id
     * @param token
     * @type 加密
     */
    public function index()
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
        $key['openid'] = $userInfo["open_id"];

        $AdviserModel = new AdviserModel;
        $result = $AdviserModel -> getStatus($key);
        return json($result);
        


    }

    /**
     * 提交
     * @param options
     * @param id
     * @param token
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
        $key['openid'] = $userInfo["open_id"];

        $AdviserModel = new AdviserModel;
        $result = $AdviserModel -> submit($key);
        return json($result);
        
    }
}