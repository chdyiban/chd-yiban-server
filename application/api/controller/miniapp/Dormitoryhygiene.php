<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Dormitoryhygiene as DormitoryhygieneModel;
use app\common\library\Token;
/**
 * 我的宿舍
 */
class Dormitoryhygiene extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * @param token
     * @type  不加密
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

        $DormitoryhygieneModel = new DormitoryhygieneModel;
        $result = $DormitoryhygieneModel -> index($key);
        if ($result["status"]) {
            $this->success($result["msg"],$result["data"]);
        } else {
            $this->error($result["msg"],$result["data"]);
        }

    }
}