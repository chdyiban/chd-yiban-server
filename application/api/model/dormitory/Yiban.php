<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;

class Yiban extends Model
{
    // 表名
    // protected $name = 'fresh_info';
    
    /**
     * 易班报名
     * @param string XWJS
     * @param string YXGW
     */
    public function apply($param,$userInfo)
    {
        $insertData = [
            "XH"    => $userInfo["XH"],
            "XWJS"  => $param["XWJS"],
            "YXGW"  => $param["YXGW"],
        ];
        $respond = Db::name("fresh_apply")->insert($insertData);
        if ($respond) {
            return ["status"=>true,"msg"=>"报名成功","data"=> null ];
        } else {
            return ["status"=>false,"msg"=>"网络错误，请稍后再试","data"=>null];
        }
    }
   
    
}