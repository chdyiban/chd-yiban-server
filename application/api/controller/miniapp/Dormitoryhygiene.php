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
            return json(['status' => 500 , 'msg' => "参数错误"]);
        } else {
            // $key = $this->request->get("XH");
            $DormitoryhygieneModel = new DormitoryhygieneModel;
            $result = $DormitoryhygieneModel -> index($key);
            // dump(json($result));
            return json($result);
        }
    }

     /**
     * 根据open_id获取学号
     * @param $open_id
     * @return 学号/工号
     */
    private function getId($open_id){
        return $user->where('open_id',$open_id)->value('portal_id');
    }
}