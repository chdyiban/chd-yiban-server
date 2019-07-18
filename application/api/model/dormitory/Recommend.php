<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
use fast\Http;

class Recommend extends Model
{
    // 表名
    protected $name = 'fresh_recommend_question';
    
    /**
     * 初始化
     * @param array userInfo
     */
    public function init_recommend($userInfo)
    {
        $questionList = $this->where("XH",$userInfo["XH"])->find();
        if ($questionList) {
            $data = [
                "step"       => 1,
                "num"  => 0,
                "list" => [],
            ];

            $recommendList = Db::name("fresh_info") -> where("XH","<>",$userInfo["XH"]) ->limit(3) ->select();
            $recommend_num = count($recommendList);
            $recommend_list = [];
            foreach ($recommendList as  $value) {
                $temp = [
                    "XH"         => $value["XH"],
                    "XM"         => $value['XM'],
                    "avatar"     => $value["avatar"],
                    "QQ"         => $value["QQ"],
                    "SYD"        => $value["SYD"],
                    "similarity" => "80%",
                ];
                $recommend_list[] = $temp;
            }
            // $data["num"] = $recommend_num;
            // $data["list"] = $recommend_list;
            
            return ["status" => true,"msg" => "", "data" => $data];
        } else {
            $data = [
                "step" => 0,
            ];
            return ["status" => true,"msg" => "", "data" => $data];
        }
    }


    /**
     * 提交问卷
     * @param array 问卷结果
     * @param array userInfo
     */
    public function submit($param,$userInfo)
    {
        $questionMap = [
            [
                "0"  => "晚11点前",
                "1"  => "晚11点-12点",
                "2"  => "晚12点后",
                "3"  => "不确定",
            ],
            [
                "0"  => "早7点前",
                "1"  => "早7点-9点",
                "2"  => "早9点后",
                "3"  => "不确定",   
            ],
            [
                "0"  => "经常",
                "1"  => "不玩",
                "2"  => "偶尔",
            ],
            [
                "0"  => "需要",
                "1"  => "无所谓",
                "2"  => "不接受",
            ],
            [
                "0"  => "外向",
                "1"  => "内向",
                "2"  => "不确定",
            ],
        ];
        $personal = $this->where("XH",$userInfo["XH"])->find();
        if (!empty($personal)) {
            return ["status" => false,"msg"=>"请勿重复提交","data" => null];
        }

        $param["option"] = empty($param["option"]) ? $param["opiton"] : $param["option"] ;
        $param["tags"]   = empty($param["tags"]) ? [] : $param["tags"] ;
        $stu_index       = $this->where("YXDM",$userInfo["YXDM"])->max("stu_index");
        $stu_index = $stu_index + 1;
        $inserData = [
            "XH"        => $userInfo["XH"],
            "YXDM"      => $userInfo["YXDM"],
            "stu_index" => $stu_index,
        ];
        foreach ($param["option"] as $key => $value) {
            $mapKey = null;
            if (!empty($value[0])) {
                $mapKey = array_search($value[0],$questionMap[$key]);
            }
            $k = "q_".($key+1);
            $inserData[$k] = $mapKey;
        }
        $tags = "";
        foreach ($param["tags"] as $key => $value) {
            if ($key == 0) {
                $tags = $value;
            } else {
                $tags = $tags.",".$value;
            }
        }
        $inserData["label"] = $tags;
        $response = $this->insert($inserData);            
        if ($response) {
            return ["status" => true, "msg" => "提交成功", "data" => null];
        } else {
            return ["status" => false, "msg" => "提交失败", "data" => null];
        }
    }

   
    
}