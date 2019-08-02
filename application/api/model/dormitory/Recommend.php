<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
use fast\Http;

class Recommend extends Model
{
    // 表名
    protected $name = 'fresh_recommend_question';
    const RECOMMEND_URL = "http://202.117.64.236:8013/recommend";
    
    /**
     * 初始化
     * @param array userInfo
     */
    public function init_recommend($userInfo)
    {
        $questionList = $this->where("XH",$userInfo["XH"])->find();
        if ($questionList) {
            $data = [
                "step" => 1,
                "num"  => 0,
                "list" => [],
            ];
            if ($userInfo["clear"] == true) {
                $recommendResult = $this->postClear($userInfo["XH"]);
            } else {
                $recommendResult = $this->postRecommend($userInfo["XH"]);
            }
            // $recommendResult = ["code" => 0,"num" => 2,"list" => []];
            if ($recommendResult["code"] != 0) {
                return ["status" => true,"msg" => $recommendResult["msg"], "data" => $data];                
            } else {
                $recommend_num = empty($recommendResult["num"]) ? 0 : $recommendResult["num"];
                $recommend_list = [];
                if (empty($recommendResult["list"])) {
                    $data["num"] = $recommend_num;
                    $data["list"] = $recommend_list;
                    // $data["num"] = 2;
                    // $data["list"] = [
                    //     [
                    //         "XH"        => "2018900007",
                    //         "XM"        => "李长宏",
                    //         "avatar"    => "#icon-default",
                    //         "QQ"        => "282813637",
                    //         "SYD"       => "青海",
                    //         "similarity"=> "0.625",
                    //     ],
                    //     [
                    //         "XH"        => "2018900046",
                    //         "XM"        => "石自强",
                    //         "avatar"    => "#icon-default",
                    //         "QQ"        => "282813637",
                    //         "SYD"       => "内蒙古",
                    //         "similarity"=> "0.625",
                    //     ],
                    // ];
                    return ["status" => true,"msg" => "", "data" => $data];
                }
                foreach ($recommendResult["list"] as  $value) {
                    $infoList = Db::view("fresh_info","XH,XM,avatar,QQ,SYD")
                                -> view("fresh_recommend_question","XH,YXDM,XBDM,stu_index","fresh_info.XH = fresh_recommend_question.XH")
                                -> where("YXDM",$value["yxdm"])
                                -> where("stu_index",$value["index"])
                                -> where("XBDM",$value["gender"])
                                -> find();
                                
                    $temp = [
                        "XH"         => $infoList["XH"],
                        "XM"         => $infoList['XM'],
                        "avatar"     => $infoList["avatar"],
                        "QQ"         => $infoList["QQ"],
                        "SYD"        => $infoList["SYD"],
                        "similarity" => $value["similarity"],
                    ];
                    $recommend_list[] = $temp;
                }
                $data["num"] = $recommend_num;
                $data["list"] = $recommend_list;
               
                return ["status" => true,"msg" => "", "data" => $data];
            }
            
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
        $stu_index       = $this->where("YXDM",$userInfo["YXDM"])->where("XBDM",$userInfo["XBDM"])->max("stu_index");
        $stu_index = $stu_index + 1;
        $insertData = [
            "XH"        => $userInfo["XH"],
            "YXDM"      => $userInfo["YXDM"],
            "XBDM"      => $userInfo["XBDM"],
            "stu_index" => $stu_index,
        ];
        foreach ($param["option"] as $key => $value) {
            $mapKey = null;
            if (!empty($value[0])) {
                $mapKey = array_search($value[0],$questionMap[$key]);
            }
            $k = "q_".($key+1);
            $insertData[$k] = $mapKey;
        }
        $tags = "";
        foreach ($param["tags"] as $key => $value) {
            if ($key == 0) {
                $tags = $value;
            } else {
                $tags = $tags.",".$value;
            }
        }
        $insertData["label"] = $tags;

        
        $response = $this->insert($insertData);   
        $this->postRecommend($userInfo["XH"]);
        if ($response) {
            return ["status" => true, "msg" => "提交成功", "data" => null];
        } else {
            return ["status" => false, "msg" => "提交失败", "data" => null];
        }
    }

    /**
     * 向推荐系统发送数据
     */
    public function postRecommend($XH)
    {
        
        $questionList = $this->where("XH",$XH)->find();
        if ($questionList) {
            $postData = [
                "ID"    => $questionList["ID"],
                "index" => $questionList["stu_index"],
                "YXDM"  => $questionList["YXDM"],
                "XBDM"  => $questionList["XBDM"],
                "q_1"   => $questionList["q_1"],
                "q_2"   => $questionList["q_2"],
                "q_3"   => $questionList["q_3"],
                "q_4"   => $questionList["q_4"],
                "q_5"   => $questionList["q_5"],
                "label" => $questionList["label"],
            ];
            // dump($postData);
            $postData = json_encode($postData);
            $recommendResult = Http::post(self::RECOMMEND_URL,$postData);
            // dump($recommendResult);
            $recommendResult = json_decode($recommendResult,true);
            return $recommendResult;
        } else {
            $data = [
                "step" => 0,
            ];
            return ["status" => true,"msg" => "", "data" => $data];
        }
    
    }

    /**
     * 清空推荐系统数据
     */
    public function postClear($XH)
    {
        $questionList = $this->where("XH",$XH)->find();
        if ($questionList) {
            $postData = [
                "ID"    => $questionList["ID"],
                "index" => $questionList["stu_index"],
                "YXDM"  => $questionList["YXDM"],
                "XBDM"  => $questionList["XBDM"],
                "q_1"   => $questionList["q_1"],
                "q_2"   => $questionList["q_2"],
                "q_3"   => $questionList["q_3"],
                "q_4"   => $questionList["q_4"],
                "q_5"   => $questionList["q_5"],
                "label" => $questionList["label"],
                "clear" => true,
            ];
       
            // dump($postData);
            $postData = json_encode($postData);
            $recommendResult = Http::post(self::RECOMMEND_URL,$postData);
            // dump($recommendResult);
            $recommendResult = json_decode($recommendResult,true);
            return $recommendResult;
        } else {
            $data = [
                "step" => 0,
            ];
            return ["status" => true,"msg" => "", "data" => $data];
        }
    }
   
    
}