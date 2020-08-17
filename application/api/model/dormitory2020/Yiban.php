<?php
namespace app\api\model\dormitory2020;


use think\Model;
use think\Db;
use think\Cache;
use think\Config;
use fast\Http;

class Yiban extends Model {
    // 表名
    protected $name = 'fresh_apply';


    const YX_URL = "";
    const CHD_ID = "5370552";
    /**
     * 获取学院微社区
     * @param string YXDM
     * @return string 
     */
    public function getCollegeUrl($YXDM)
    {
        $group_id = Db::name("dict_college")->where("YXDM",$YXDM)->field("yb_group_id")->find();
        if (!empty($group_id["yb_group_id"])) {
            $group_id = $group_id["yb_group_id"];
            //PC端学院微社区地址
            // $url = "http://www.yiban.cn/Org/orglistShow/puid/5370552/group_id/$group_id/type/forum";
            //移动端微社区跳转地址
            $url = "https://www.yiban.cn/forum/article/list/groupid/$group_id/puserid/5370552";
            return ["status" => true, "data" => ["url" => $url]];
        }
        return ["status" => false, "msg" => "未找到对应学院！"];
    }

    /**
     * 获取班级微社区
     * @param string BJDM
     * @return string 
     */
    public function getClassUrl($BJDM)
    {
        $group_id = Db::name("dict_class")->where("BJDM",$BJDM)->field("group_id")->find();
        if (!empty($group_id["group_id"])) {
            $group_id = $group_id["yb_group_id"];
            //移动端微社区跳转地址
            $url = "https://www.yiban.cn/forum/article/list/groupid/$group_id/puserid/11164811";
            return ["status" => true, "data" => ["url" => $url]];
        }
        return ["status" => false, "msg" => "未找到对应班级！"];
    }


    
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