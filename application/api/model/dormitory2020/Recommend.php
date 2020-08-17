<?php

namespace app\api\model\dormitory2020;

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
        if( empty($userInfo["clear"])){
            $userInfo["clear"] = false;
        };
        if ($questionList) {

            if ($questionList["status"] == "close") {
                $data = [
                    "step" => 2,
                    "num"  => 0,
                    "list" => [],
                ];
                return ["status" => true,"msg" => "该功能被关闭啦", "data" => $data];                
            }

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
            if ($recommendResult["code"] != 0) {
                return ["status" => true,"msg" => $recommendResult["msg"], "data" => $data];                
            } else {
                $recommend_num = empty($recommendResult["num"]) ? 0 : $recommendResult["num"];
                $recommend_list = [];
                if (empty($recommendResult["list"])) {
                    $data["num"] = $recommend_num;
                    $data["list"] = $recommend_list;
                    return ["status" => true,"msg" => "", "data" => $data];
                }
                foreach ($recommendResult["list"] as  $value) {
                    $infoList = Db::view("fresh_info","XH,XM,avatar,QQ,SYD")
                                -> view("fresh_recommend_question","XH,YXDM,XBDM,stu_index,status","fresh_info.XH = fresh_recommend_question.XH")
                                -> where("YXDM",$value["yxdm"])
                                -> where("stu_index",$value["index"])
                                -> where("XBDM",$value["gender"])
                                -> find();
                    //将关闭功能的人过滤
                    if ($infoList["status"] == "open") {
                        $temp = [
                            "XH"         => $infoList["XH"],
                            "XM"         => $infoList['XM'],
                            "avatar"     => $infoList["avatar"],
                            "QQ"         => $infoList["QQ"],
                            "SYD"        => $infoList["SYD"],
                            "similarity" => number_format($value["similarity"],2),
                        ];
                        $recommend_list[] = $temp;
                    }
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
        if (empty($param["option"]) || count($param["option"]) < 5 ) {
            return ["status" => false, "msg" => "个人习惯必选！", "data" => null];                        
        }
        $param["tags"]   = empty($param["tags"]) ? [] : $param["tags"] ;
        if (count($param["tags"]) > 5) {
            return ["status" => false, "msg" => "标签至多5项", "data" => null];                        
        }
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

    /**
     * 修改推荐状态
     * @param array $param["action"] open|close
     * @param array 
     */
   public function set($param,$userInfo)
   {
       if (empty($param["action"])) {
            return ["status" => false,"msg" => "param error!", "data" => null];
       }
       if ($param["action"] == "close") {
           $userRecommendInfo = $this->where("XH",$userInfo["XH"])->find();
           if (empty($userRecommendInfo)) {
                return ["status" => false,"msg" => "未填写问卷", "data" => null];
            }
            if ($userRecommendInfo["status"] == "close") {
                return ["status" => false,"msg" => "未开启此功能", "data" => null];
           }
           $res = $this->where("XH",$userInfo["XH"])->update(["status" => "close"]);
           if ($res) {
                return ["status" => true,"msg" => "关闭了就没办法推荐好友了哦,真可惜!", "data" => null];
            } else {
                return ["status" => false,"msg" => "网络出现问题，请稍后重试", "data" => null];
           }
       } elseif ($param["action"] == "open") {
            $userRecommendInfo = $this->where("XH",$userInfo["XH"])->find();
            if (empty($userRecommendInfo)) {
                return ["status" => false,"msg" => "未填写问卷", "data" => null];
            }
            if ($userRecommendInfo["status"] == "open") {
                return ["status" => false,"msg" => "已开启此功能", "data" => null];
            }
            $res = $this->where("XH",$userInfo["XH"])->update(["status" => "open"]);
            if ($res) {
                return ["status" => true,"msg" => "开启推荐功能啦,快联系推荐的好友吧！", "data" => null];
            } else {
                return ["status" => false,"msg" => "网络出现问题，请稍后重试", "data" => null];
            }
        } else {
            return ["status" => false,"msg" => "params error!", "data" => null];
       }
   }
    
}