<?php

namespace app\api\model\dormitory2020;

use think\Model;
use think\Db;

class User extends Model
{
    // 表名
    protected $name = 'fresh_info';
    
    /**
     * 获取用户相关信息
     * @param string param["XH"]
     * @return array
     * 5次SQL
     */
    public function getMeInfo($param)
    {
        $result = [
            "user_info" => [],
            "user_step" => [],
            "dormitory" => [],
            "wxuser"    =>  [],
        ];
        if (empty($param["XH"])) {
            return ["status" => false,"msg" =>"param error!","data" => null];
        }
        $result["user_info"] = Db::view("fresh_info","XM,XH,YXDM,ZYMC,XBDM,LXDH,QQ,avatar,SYD,BJDM,unionid")
                            -> view("dict_college","YXDM,YXMC","fresh_info.YXDM = dict_college.YXDM")
                            -> where("fresh_info.XH",$param["XH"])
                            -> find();
        //标记床位
        $markList = Db::name("fresh_mark")->field("SSDM,CH")->where("XH",$param["XH"])->find();
        //身高体重
        // $userBodyInfo = Db::name("fresh_questionnaire_first")->where("XH",$param["XH"])->field("BRSG,BRTZ")->find();
        // $result["user_info"]["BRSG"] = empty($userBodyInfo["BRSG"]) ? "" : $userBodyInfo["BRSG"];
        // $result["user_info"]["BRTZ"] = empty($userBodyInfo["BRTZ"]) ? "" : $userBodyInfo["BRTZ"];
        $result["user_info"]["BJCW"] = empty($markList) ? "" : $markList["SSDM"]."-".$markList["CH"]; 
        $nowStep = $this->getSteps($param);
        $nowData = $this->getStepData($nowStep,$param["XH"]);
        $nowData["now"] = $nowStep;
        // $result["user_step"] = ["now" => $nowStep,"data" => $nowData];
        $result["user_step"] = $nowData;
        //读取配置文件决定选宿舍状态，迁移至基类getstep方法下
        $result["dormitory"] = $param["step"];
        $result["wxuser"] = Db::name("wx_unionid_user")->where("unionid",$result["user_info"]["unionid"])->field("avatar,nickname")->find();
        $result["wxuser"]["nickname"] = base64_decode($result["wxuser"]["nickname"]);
        unset($result["user_info"]["unionid"]);
        return ["status" => true, "msg" => null, "data" => $result];
    }

    /**
     * 修改用户头像
     * @param string avatar
     * @return array 
     */
    public function avatar($param,$userId)
    {
        if (empty($param["avatar"])) {
            return ["status" => false,"msg" =>"param error!","data" => null];
        }
        $response = $this->where("ID",$userId) -> update(["avatar" => $param["avatar"]]);
        if ($response) {
            return ["status" => true,"msg" =>"更换成功","data" => ["avatar"=>$param["avatar"]]];
        } else {
            return ["status" => true,"msg" =>"更换失败，请稍后重试","data" => null];
        }
    }


    /**
     * 修改用户QQ
     * @param string qq
     * @return array 
     */
    public function qq($param,$userId)
    {
        if (empty($param["qq"])) {
            return ["status" => false,"msg" =>"param error!","data" => null];
        }
        $response = $this->where("ID",$userId) -> update(["QQ" => $param["qq"]]);
        return ["status" => true,"msg" =>"提交成功","data" => ["qq"=>$param["qq"]]];

    }

    /**
     * 获取用户当前步骤
     * @param string XH
     * @return string
     */

    private function getSteps($param){
        //判断信息是否完善
        $userXh = $param["XH"];
        $isInfoExist = Db::name('fresh_questionnaire_first') -> where('XH', $userXh) -> field('ID,XH') -> find();
        $isListExist = Db::name('fresh_result') -> where('XH', $userXh) -> field('ID,XH,status') -> find();
        if (empty($isInfoExist)) {
            return 'QUE';
        } elseif ($param["step"]["step"] == "NST"){
            return 'SEL';
        } elseif (empty($isListExist)) {
            return 'SEL';
        } elseif ($isListExist['status'] == "waited"){
            return "CON";
        } else {
            return "FIN";
        }
        
    }

    /**
     * 获取当前用户步骤附带数据
     * @param string step
     * @param int XH
     * @return array
     */
    private function getStepData($step,$XH)
    {
        switch ($step) {
            case 'CON':
                $returnList = [];
                $selectList = Db::name("fresh_result")->where("XH",$XH)->field("YXDM,XH,SSDM,CH,XQ,SDSJ")->find();
                if (!empty($selectList)) {
                    $returnList = [
                        "XH"       => $selectList["XH"],
                        "XQ"       => $selectList["XQ"] == "north" ? "渭水" : "雁塔",
                        "building" => explode("#",$selectList["SSDM"])[0],
                        "room"     => explode("#",$selectList["SSDM"])[1],
                        "bed"      => $selectList["CH"],
                        "deadline" => date("Y-m-d H:i:s",$selectList["SDSJ"]+3600 ),
                    ];
                }

                $dormitoryInfo = Db::name("fresh_dormitory_".$selectList["XQ"])
                                ->where("SSDM",$selectList["SSDM"])
                                ->where("YXDM",$selectList["YXDM"])
                                ->field("CPXZ")
                                ->find();
                $bedCount = strlen($dormitoryInfo["CPXZ"]);
                $returnList["cost"] = $bedCount == 4 ? 1200 : 900; 
                return $returnList;
                
            case "FIN":
                $returnList = [];
                $selectList = Db::name("fresh_result")->where("XH",$XH)->field("YXDM,XH,SSDM,CH,XQ")->find();
                if (!empty($selectList)) {
                    $returnList = [
                        "XH"       => $selectList["XH"],
                        "XQ"       => $selectList["XQ"] == "north" ? "渭水" : "雁塔",
                        "building" => explode("#",$selectList["SSDM"])[0],
                        "room"     => explode("#",$selectList["SSDM"])[1],
                        "bed"      => $selectList["CH"],
                    ];
                }

                $dormitoryInfo = Db::name("fresh_dormitory_".$selectList["XQ"])
                                ->where("SSDM",$selectList["SSDM"])
                                ->where("YXDM",$selectList["YXDM"])
                                ->field("CPXZ")
                                ->find();
                $bedCount = strlen($dormitoryInfo["CPXZ"]);
                $returnList["cost"] = $bedCount == 4 ? 1200 : 900; 
                return $returnList;
            
            default:
                # code...
                break;
        }
    }
    
}