<?php

namespace app\api\model\dormitory;

use think\Model;
use think\Db;
use think\Config;

class Extra extends Model
{
    // 表名
    // protected $name = 'fresh_info';
    const MAP_URL = "http://cdn.knocks.tech/college_maps/";
    
    /**
     * 获取选宿问题说明
     */
    public function introduction()
    {
        $list = Db::name("fresh_introduction")->select();
        return ["status"=>true,"msg"=>"查询成功","data"=>["question" => $list]];
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

            $temp["position"] = $positionSite;
            $temp["label"]["content"] = "可选:".$LH."号学生公寓";
            $temp["map"]["value"] = $XQ."-".$LH;
            $temp["map"]["src"] = self::MAP_URL.$college."_".$XB.".jpg";
            if ($XQ == "north") {
                $temp["map"]["name"] = $LH."号学生公寓(渭水)";
            } else {
                $temp["map"]["name"] = $LH."号学生公寓(雁塔)";
            }
            $return["markers"][] = $temp;
        }    
        return ["status" => true,"msg" => "", "data" => $return ];    

    }
   
    
}