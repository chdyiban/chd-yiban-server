<?php

namespace app\api\controller\dormitory2020;

use app\api\controller\dormitory2020\Api;
use think\Config;
use app\api\model\dormitory2020\Recommend as RecommendModel;


/**
 * 
 */
class Recommend extends Api
{
    protected $noNeedBindPortal = [];

    /**
     * 问卷初始化
     *
     */
    public function init_recommend()
    {
        $RecommendModel = new RecommendModel();
        $result = $RecommendModel -> init_recommend($this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

    public function submit()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $RecommendModel = new RecommendModel();
        $result = $RecommendModel -> submit($key,$this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

    /**
     * 关闭或开启推荐功能
     * @param string action close|open
     */
    public function set()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $RecommendModel = new RecommendModel();
        $result = $RecommendModel -> set($key,$this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

}