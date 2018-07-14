<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use think\Db;
use app\api\model\Dormitory as DormitoryModel;
/**
 * 
 */
class Dormitoryredis extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    private $loginInfo = null;
    private $token = null;
    private $userInfo = null;

    const LOCAL_URL = "http://localhost:8080/yibanbx/public/api/dormitory/submit";
    /**
     * 实时监控redis,一旦有了新的值则将值提交给submit方法.
     */
    public function getredis()
    {
        // 首先加载Redis组件
        $redis = new \Redis();
        $redis -> connect('127.0.0.1', 6379);
        $redis_name = "order_msg";
        while (true) {
            $data = $redis -> lpop($redis_name);
            if ($data) {
                $key = base64_encode($data);
                $res = Http::post(self::LOCAL_URL, $data);
            }
        }
    }

}







