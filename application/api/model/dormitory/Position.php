<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;


class Position extends Model
{
    // 表名
    protected $name = 'fresh_result';
    
    /**
     * 初始化位置信息
     * @param array userInfo
     */
    public function init_position($userInfo)
    {
        $XH = $userInfo["XH"];
        $XQ = $userInfo["XQ"];
        $personalInfo = $this->where("XH",$userInfo["XH"])->where("status","finished")->find();

        if (empty($personalInfo)) {
            return ["status" => false,"msg" => "请先完成选宿舍", "data" => null];
        }
        $SSDM = $personalInfo["SSDM"];
        $roommateLocation = [];
        $meLocation       = [];
        $roommatesInfo =  Db::view("fresh_result")
                        -> view("fresh_info","XH,XM,QQ,avatar","fresh_result.XH = fresh_info.XH")
                        -> order("CH")
                        -> where("status","finished")
                        -> where("XQ",$XQ)
                        -> where("SSDM",$SSDM)
                        -> select();
        foreach ($roommatesInfo as $key => $value) {

            if ($value["XH"] == $XH) {
                if (!empty($value["latitude"])) {
                    $meLocation = [
                        "position"   => [
                            "0" => $value["longitude"],
                            "1" => $value["latitude"]
                        ],
                        "avatar" => $value["avatar"],
                        "CH"     => $value["CH"],
                        "XM"     => $value["XM"],
                        "status" => true,
                    ];
                } else {
                    //本人没有授权位置信息
                    $meLocation = [
                        "position"   => [],
                        "avatar" => $value["avatar"],
                        "CH"     => $value["CH"],
                        "XM"     => $value["XM"],
                        "status" => false,
                    ];
                }
            
            }
             
            if (!empty($value["latitude"])) {
                $roommateLocation[] = [
                    "position"   => [
                        "0" => $value["longitude"],
                        "1" => $value["latitude"]
                    ],
                    "avatar" => $value["avatar"],
                    "CH"     => $value["CH"],
                    "XM"     => $value["XM"],
                    "status" => true,
                ];
            } else {
                //本人没有授权位置信息
                $roommateLocation[] = [
                    "position"   => [],
                    "avatar" => "#icon-question-circle",
                    "CH"     => $value["CH"],
                    "XM"     => $value["XM"],
                    "status" => false,
                ];
            }
            

        }

        $returnData = [
            "me"            => $meLocation,
            "roommatesList" => $roommateLocation,
        ];
        return ["status" => true,"msg" => "", "data" => $returnData];
    }


    /**
     * 提交位置信息
     * @param array 用户经纬度
     * @param array userInfo
     */
    public function submit($param,$userInfo)
    {
        $personal = $this->where("XH",$userInfo["XH"])->field("latitude,longitude")->find();
        if (!empty($personal["longitude"])) {
            return ["status" => false,"msg"=>"请勿重复提交","data" => null];
        }


        $updateData = [
            "latitude"      => $param["lat"],
            "longitude"     => $param["lng"],
        ];
        $response = $this->where("XH",$userInfo["XH"])->update($updateData);            
        if ($response) {
            return ["status" => true, "msg" => "提交成功", "data" => null];
        } else {
            return ["status" => false, "msg" => "提交失败", "data" => null];
        }
    }

   
    
}