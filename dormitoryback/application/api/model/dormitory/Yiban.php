<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;

class Yiban extends Model
{
    // 表名
    protected $name = 'fresh_apply';
    
    /**
     * 易班报名
     * @param string group
     * @param string action get|set
     */
    public function apply($param,$userInfo)
    {
        $XH = $userInfo["XH"];
        if (empty($param["action"])) {
            return ["status" => false, "msg" => "param error!","data" => null];
        }

        $checkApply = $this->checkApply($XH);
        if ($param["action"] == "get") {
            if ($checkApply["status"]) {
                return ["status" => true, "msg" => "已经报名" ,
                        "data" => ["apply" => true,"group" => $checkApply["group"]]
                    ];
            } else {
                return ["status" => true, "msg" => "未报名" ,"data" => ["apply" => false]];
            }
        } elseif ($param["action"] == "set") {

            if (!$checkApply["status"]) {
                $insertData = [
                    "XH"    => $userInfo["XH"],
                    "YXGW"  => $param["group"][0],
                ];
                $respond = $this->insert($insertData);
                if ($respond) {
                    return ["status"=>true,"msg"=>"报名成功","data"=> null ];
                } else {
                    return ["status"=>false,"msg"=>"网络错误，请稍后再试","data"=>null];
                }
            } else {
                return ["status" => false, "msg" => "已经报过名了哦！" ,
                    "data" => ["apply" => true,"group" => $checkApply["group"]]
                ];
            }
        }
    }

    /**
     * 判断是否已经报名
     * @param string XH
     * @return array [true=>已经报名,group=>组别]
     */
    private function checkApply($XH)
    {
        $applyMap = [
            "1"  => "行政办公组",
            "2"  => "平台运营组",
            "3"  => "人事管理组",
            "4"  => "视觉设计组",
            "5"  => "技术开发组",
        ];
        $applyList = $this->where("XH",$XH) -> find();
        if (empty($applyList)) {
            return ["status" => false, "group" => null];
        } else {
            return ["status" => true, "group" => $applyMap[$applyList["YXGW"]]];
        }
    }


   
    
}