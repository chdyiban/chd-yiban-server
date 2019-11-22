<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Form as FormModel;

/**
 * 万能表单
 */
class Form extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $this->error("params error!");
            // return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            // $key = [];
            $FormModel = new FormModel;
            $result = $FormModel -> initForm($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
                // return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                $this->error($result["msg"],$result["data"]);
                // return json(["status" => 500,"msg" => $result["msg"],"data" => $result["data"]]);
            }
        }
    }
    /**
     * 获取表单详情
     * @param openid
     * @param form_id
     */
    public function detail()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid']) || empty($key["id"])) {
            return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            // $key = ["openid" => "o5WD50I1ZhBv7aztZUsaPZRLE30Q","id" => "1"];
            $FormModel = new FormModel;
            $result = $FormModel -> detail($key);
            if ($result["status"]) {
                return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                return json(["status" => 500,"msg" => $result["msg"],"data" => $result["data"]]);
            }
        }
    }

    /**
     * 提交问卷答案
     */
    public function submit(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // dump($key);
        if (empty($key['id']) || empty($key["data"]) || empty($key['openid'])) {
            return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            $FormModel = new FormModel;
            // dump($key);
            $result = $FormModel -> submit($key);
            if ($result["status"]) {
                return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                return json(["status" => 500,"msg" => $result["msg"], "data" => $result["data"]]);
            }
        }
    }
}