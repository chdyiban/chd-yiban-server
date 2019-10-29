<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use app\api\model\Clock as ClockModel;

/**
 * 打卡应用
 */
class Clock extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            // $key = ["openid" => "o5WD50I1ZhBv7aztZUsaPZRLE30Q","activity_id" => "1"];
            $ClockModel = new ClockModel;
            $result = $ClockModel -> index($key);
            if ($result["status"]) {
                return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                return json(["status" => 500,"msg" => $result["msg"],"data" => $result["data"]]);
            }
        }
    }
    /**
     * 报名
     */
    public function apply()
    {
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $this->error("参数错误");
        } else {
            $ClockModel = new ClockModel;
            $result = $ClockModel -> apply($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
            } else {
                $this->error($result["msg"],$result["data"]);
            }
        }
    }

    /**
     * 打卡
     */
    public function clock(){
        //解析后应对签名参数进行验证
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // dump($key);
        if ( empty($key['openid'])) {
            $this->eroor("参数错误");
        } else {
            // $key = ["openid" => "o5WD50I1ZhBv7aztZUsaPZRLE30Q","activity_id" => "1"];
            $ClockModel = new ClockModel;
            $result = $ClockModel -> clock($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
                // return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                $this->error($result["msg"],$result["data"]);
                // return json(["status" => 500,"msg" => $result["msg"], "data" => $result["data"]]);
            }
        }
    }
}