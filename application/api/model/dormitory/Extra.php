<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
use think\Config;

class Extra extends Model
{
    // 表名
    // protected $name = 'fresh_info';
    const MAP_URL = "http://cdn.knocks.tech/college_2019/";
    
    /**
     * 获取选宿问题说明
     */
    public function question()
    {
        $list = Db::name("fresh_introduction")->select();
        $returnData = [];
        foreach ($list as $key => $value) {
            $temp = [
                "title" => $value["title"],
                "desc"  => $value["content"],
            ];
            $returnData[] = $temp;
        }
        return ["status"=>true,"msg"=>"查询成功","data"=> $returnData ];
    }

    /**
     * 获取学生可选宿舍
     * @param array $userInfo
     */
    public function map($userInfo)
    {
        $XQ        = $userInfo["XQ"];
        $college   = $userInfo["YXDM"]; 
        $XB        = $userInfo["XBDM"];

        $return = [ 
            "zoom"    => 16,
            "center"  => [108.911299,34.372718],
            "markers" => [],
        ];

        $roomList = Db::name("fresh_dormitory_".$XQ)
                    -> where("YXDM",$college)
                    -> where("XB",$XB)
                    -> field("LH")
                    -> group("LH")
                    -> select();
        foreach ($roomList as $key => $value) {
            $temp = [
                "position"  => [],
                "label"     => [
                    "content"  => "",
                    "offset"   => [-20,40],
                ],
                "map"       => [
                    "value" => "",
                    "name"  => "",
                    "src"   => "",
                ]
            ]; 

            $k    = "map.".$XQ;
            $LH   = $value["LH"];
            $positionSite = Config::get("map.north")[$LH];
            $mapName      = Config::get("dormitoryMap")[$college][$XB][$LH];
            $temp["position"] = $positionSite;
            $temp["label"]["content"] = "可选:".$LH."号学生公寓";
            $temp["map"]["value"] = $XQ."-".$LH;
            $temp["map"]["src"] = self::MAP_URL.$mapName.".jpg";
            if ($XQ == "north") {
                $temp["map"]["name"] = $LH."号学生公寓(渭水)";
            } else {
                $temp["map"]["name"] = $LH."号学生公寓(雁塔)";
            }
            $return["markers"][] = $temp;
        }    
        return ["status" => true,"msg" => "", "data" => $return ];    

    }

    /**
     * 获取学工部通知
     */
    public function notice()
    {
        $data = Db::name("fresh_xgb")->where("ID",1)->find();

        if (!empty($data)) {
            $returnData = [
                "title"  => $data["title"],
                "content"=> $data["content"],
                "meta"   => [
                    "source" => "学生工作部(处)社区管理中心",
                    "date"   => "2019-08-10",
                ]
            ];
            return ["status" => true,"msg" => "", "data" => $returnData ];                
        } else {
            return ["status" => false,"msg" => "暂无通知", "data" => [] ];                
        }
    }
   
    
}


