<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Dormitoryhygiene as DormitoryhygieneModel;

/**
 * 我的宿舍
 */
class Dormitoryhygiene extends Api
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
            // $key = "o5WD50Oc4KM3eSn35ibzPQ8TF6oY";
            // $key = $this->request->get("XH");
            $DormitoryhygieneModel = new DormitoryhygieneModel;
            $result = $DormitoryhygieneModel -> index($key);
            if ($result["status"]) {
                $this->success($result["msg"],$result["data"]);
                // return json(["status" => 200,"msg" => $result["msg"],"data" => $result["data"]]);
            } else {
                $this->error($result["msg"],$result["data"]);
            }
            // return json($result);
        }
    }
}