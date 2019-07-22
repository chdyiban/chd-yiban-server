<?php

namespace app\api\controller\dormitory2019;

use app\api\controller\dormitory2019\Api;
use app\api\model\dormitory\Yiban as YibanModel;


/**
 * 
 */
class Yiban extends Api
{
    protected $noNeedLogin = [];

    public function apply()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        $YibanModel = new YibanModel();
        $data = $YibanModel->apply($key,$this->_user);
        if ($data["status"]) {
            $this->success($data["msg"],$data["data"]);
        } else {
            $this->error($data["msg"],$data["data"]);
        }
    }

}