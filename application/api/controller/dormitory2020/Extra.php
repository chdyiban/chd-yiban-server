<?php

namespace app\api\controller\dormitory2020;

use app\api\controller\dormitory2020\Api;
use app\api\model\dormitory2020\Extra as ExtraModel;


/**
 * 
 */
class Extra extends Api
{
    protected $noNeedBindPortal = ["question","notice"];


    /**
     * 获取选宿舍说明
     */
    public function question()
    {
        $ExtraModel = new ExtraModel();
        $result = $ExtraModel -> question();
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

    /**
     * 获取宿舍坐标平面图
     */

    public function map()
    {
        $ExtraModel = new ExtraModel();
        $result = $ExtraModel -> map($this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }
    /**
     * 获取学工部通知
     */
    public function notice()
    {
        $ExtraModel = new ExtraModel();
        $result = $ExtraModel -> notice($this->_user);
        if ($result["status"]) {
            $this->success($result["msg"], $result["data"]);
        } else {
            $this->error($result["msg"], $result["data"]);
        }
    }

}