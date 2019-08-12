<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
// use think\Config;

class User extends Model
{
    // 表名
    protected $name = 'fresh_info';
    

    /**
     * 获取用户相关信息
     * @param string param["XH"]
     * @param int    param["ID"]
     * @return array
     * 5次SQL
     */
    public function getMeInfo($param)
    {
        $result = [
            "user_info" => [],
            "user_step" => [],
            "dormitory" => [],
        ];
        if (empty($param["XH"])) {
            return ["status" => false,"msg" =>"param error!","data" => null];
        }
        
        $result["user_info"] = Db::view("fresh_info","XM,XH,YXDM,ZYMC,XBDM,LXDH,QQ,avatar,SYD,BJDM")
                            -> view("dict_college","YXDM,YXMC","fresh_info.YXDM = dict_college.YXDM")
                            -> where("fresh_info.XH",$param["XH"])
                            -> find();
        //标记床位
        $markList = Db::name("fresh_mark")->field("SSDM,CH")->where("XH",$param["XH"])->find();
        $result["user_info"]["BJCW"] = empty($markList) ? "" : $markList["SSDM"]."-".$markList["CH"]; 
        $nowStep = $this->getSteps($param);
        $nowData = $this->getStepData($nowStep,$param["XH"]);
        $nowData["now"] = $nowStep;
        // $result["user_step"] = ["now" => $nowStep,"data" => $nowData];
        $result["user_step"] = $nowData;
        //读取配置文件决定选宿舍状态，迁移至基类getstep方法下
        $result["dormitory"] = $param["step"];

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
     * 获取新生ID验证账号密码正确性
     * @param string $user["studentID"]
     * @param string $user["password"]
     * @return bool|int 
     * 
     */
    public function check($user){
        //新生数据库进行比对，若成功则返回userid ，若不成功返回false
        if (empty($user['studentID'] || empty($user['password']))) {
            return false;
        } else {
            $info = $this-> where('XH', $user['studentID'])
                        -> field('SFZH,ID')
                        -> find(); 
            if (empty($info)) {
                return false;
            } else {
                $id_card = $info['SFZH'];
                $password = substr($id_card, -6);
                if ($user['password'] == $password) {
                    $userid = $info['ID'];
                    return $userid;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 获取用户当前步骤
     * @param string XH
     * @return string
     */

    private function getSteps($param){
        //判断信息是否完善
        $userXh = $param["XH"];
        $isInfoExist = Db::name('fresh_questionnaire_base') -> where('XH', $userXh) -> field('ID,XH') -> find();
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