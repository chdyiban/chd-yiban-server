<?php

namespace app\api\controller\dormitory2019;

use app\api\controller\dormitory2019\Api;
use app\api\model\dormitory\Position as PositionModel;


/**
 * 
 */
class Position extends Api
{
    protected $noNeedLogin = [];

    /**
     * 用户位置初始化
     *
     */
    public function init_position()
    {
        $PositionModel = new PositionModel();
        $result = $PositionModel -> init_position($this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

    public function submit()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $PositionModel = new PositionModel();
        $result = $PositionModel -> submit($key,$this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

}