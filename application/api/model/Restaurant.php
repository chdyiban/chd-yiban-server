<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;
use think\Validate;

class Restaurant extends Model
{
    // 表名
    protected $name = '';

    const GET_MSG_URL = 'http://canteen.chd.edu.cn/restaurantbackstage/restaurant/visitorsFloweate/selectRestaurantVisitorsFloweateWithNow';

    /**
     * 获取餐厅拥挤情况数据
     */
    public function getMsg()
    {
        $data =  json_decode(Http::get(self::GET_MSG_URL),"true");
        if ($data["code"] == 200) {
            return ["status" => true,"msg" => "查询成功","data" => $data["data"]];
        } 
        return ["status" => false,"msg" => "查询失败，请稍后再试","data" => "" ];

    }

 
}