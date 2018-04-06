<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;

/**
 * 课表查询
 */
class Course extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function get_list(){
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => []
        ];
        return json($info);
    }
}