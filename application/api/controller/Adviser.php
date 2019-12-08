<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Adviser as AdviserModel;

/**
 * 班主任评价
 */
class Adviser extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        //$key = $this->request->param();
        if (empty($key['id']) || empty($key['openid'])) {
            $this->error("params error");
            // return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            $AdviserModel = new AdviserModel;
            $result = $AdviserModel -> getStatus($key);
            return json($result);
        }


    }


    public function submit(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['id']) || empty($key['options']) || empty($key['openid'])) {
            $this->error("params error");
            // return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            $AdviserModel = new AdviserModel;
            $result = $AdviserModel -> submit($key);
            return json($result);
        }
    }
}