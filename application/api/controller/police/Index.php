<?php

namespace app\api\controller\police;

use app\api\controller\police\Api;
use think\Db;

/**
 * 警务系统
 */
class Index extends Api
{

    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];

    /**
     * 获取区域信息
     */
    public function get_group()
    {
        $result = Db::name("police_category")->select();
        foreach ($result as $key => &$value) {
          $value["latitudes"] = floatval($value["latitudes"]);
          $value["longitudes"] = floatval($value["longitudes"]);
        }
        $this->success("success",$result);
    }
    /**
     * 获取区域人员信息
     * @param $key["id"]
     */
    public function get_list()
    {
        $key = json_decode(urldecode(base64_decode($this->request->post('key'))),true);
        if (empty($key["id"])) {
            $this->error("params error!");
        }
        $result = Db::name("police_people")
                -> where("category_id",$key["id"])
                -> select();
        foreach ($result as $key => &$value) {
          $value["key"] = $value["id"];
        }
        $this->success("success",$result);
    }

  
}