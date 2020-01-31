<?php

namespace app\api\controller;

use app\api\controller\Bigdata as BigdataController;
use app\common\controller\Api;

/**
 * 获取成绩进行分析接口
 */
class Score extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function index()
    {
        $XH = $this->request->get("XH");
        if (empty($XH)) {
            $this->error("params error!");
        }
        $score = new BigdataController;
        $access_token = $score->getAccessToken();
        if ($access_token["status"] == false) {
            $this->error($access_token["msg"]);
        }
        $access_token = $access_token["access_token"];
        $params = ["access_token" => $access_token,"XH" => $XH];
        $data = array_reverse($score->getScore($params));
        $this->success("success",$data);
    }

}
