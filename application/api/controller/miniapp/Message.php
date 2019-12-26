<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use think\Db;

use app\api\model\Message as MessageModel;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;

/**
 * 小程序信息查询 电话查询/校车时刻查询
 */
class Message extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];


    /**
     * 查询常用电话
     * @param token
     * @type 不加密
     */
    public function phone()
    {
        //解析后应对签名参数进行验证
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        // $key = $this->request->param();
        // if (empty($key['token'])) {
        //     $this->error("access error");
        // }
        // $token = $key['token'];
        // $tokenInfo = Token::get($token);
        // if (empty($tokenInfo)) {
        //     $this->error("Token expired");
        // }
        // $userId = $tokenInfo['user_id'];
        // $userWxInfo = WxuserModel::get($userId);
        // if (empty($userWxInfo["portal_id"])) {
        //     $this->error("请先绑定学号！");
        // }
        // $param["XH"] = $userWxInfo["portal_id"];
        $param["XH"] = "2017902148";
        $MessageModel = new MessageModel;
        $result = $MessageModel -> getPhone($param);

        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } 
        $this->error($result["msg"],$result["data"]);     
    }

}