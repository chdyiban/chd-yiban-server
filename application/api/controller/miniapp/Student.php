<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\common\library\Token;
/**
 * 学生查询
 */
class Student extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * @param token
     * @type 不加密
     */
    public function get_info(){
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
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }

        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => [
                'total'=>'2',
                'rows'=>[
                    ['xm'=>'杨测试','xh'=>'2402090214','xb'=>'男','yxm'=>'信息工程学院','zym'=>'计算机科学与技术','nj'=>'2017','bj'=>'20172402'],
                    ['xm'=>'张三','xh'=>'2402090215'],
                ]
            ]
        ];
        return json($info);
    }
}