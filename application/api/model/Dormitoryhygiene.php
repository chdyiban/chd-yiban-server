<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;

class Dormitoryhygiene extends Model
{
    // 表名
    protected $name = 'dormitory_beds';
    
    /**
     * 我的宿舍功能获取信息
     * @url https://www.tapd.cn/47906839/markdown_wikis/#1147906839001000034
     */
    public function index($key){
        $openid = $key["openid"];
        $XH = Db::name("wx_user")->where('open_id',$openid)->value('portal_id');
        if (!empty($XH)) {
            $myPanel = $this->getInfo($XH);
            $notice = $this -> getNotice();
            $listPanel = $this-> getCheckResult($XH);
            
            $result = [
                "myPanel"   => $myPanel,
                "notice"    => $notice,
                "listPanel" => $listPanel,
            ];
        } else {
            $result = [
                "myPanel"   => "",
                "notice"    => "",
                "listPanel" => "",
            ];
        }
        // $XH = $key;

        return ["status" => true,"msg" => "success","data" =>$result];
    }

    /**
     * 获取学生相关信息
     * @param int XH
     * @return array 
     */
    public function getInfo($XH)
    {

        if (empty($XH)) {
            return null;
        }
        $stuInfo = Db::name("stu_detail") -> where("XH",$XH) -> field("XM") ->find();

        $dormitoryInfo  = Db::view("dormitory_beds",["ID" => "bedID","*"])
                        -> view("dormitory_rooms","ID,LH,SSH","dormitory_beds.FYID = dormitory_rooms.ID")
                        -> where("dormitory_beds.XH",$XH)
                        -> find();
        if (empty($dormitoryInfo)) {
            return [];
        }
        $roommates = Db::view("dormitory_beds")
                    -> view("stu_detail","XH,XM","dormitory_beds.XH = stu_detail.XH")
                    -> where("dormitory_beds.FYID",$dormitoryInfo["FYID"])
                    -> where("dormitory_beds.XH","<>",$dormitoryInfo["XH"])
                    -> select();
        $roommatesName = [];
        if (!empty($roommates)) {
            foreach ($roommates as $key => $value) {
                // $roommatesName = $value["XM"].",".$roommatesName;
                $roommatesName[] = $value["XM"];
                
            }
        }
        $myPanel = [
            "name"      => $stuInfo["XM"],
            // "dormitory" => $dormitoryInfo["LH"]."#".$dormitoryInfo["SSH"]."-".$dormitoryInfo["CH"],
            "dormitory" => $dormitoryInfo["LH"]."#".$dormitoryInfo["SSH"],
            "roommates" => $roommatesName,
        ];
        return $myPanel;
    }

    /**
     * 获取宿舍检查通知
     */
    public function getNotice()
    {
        $channel_name = "宿舍检查";
        $channel = Db::name("cms_channel") -> where("name",$channel_name) -> field("id") -> find();
        $channel_id = isset($channel["id"]) ? $channel["id"] : "";
        $article_list = Db::name("cms_archives") 
                    -> where("channel_id",$channel_id)
                    -> order("id")
                    -> field("id,title,createtime") 
                    -> select();
        $temp = [
            "date" => date("Y-m-d", $article_list[0]["createtime"]),
            "id"   => $article_list[0]["id"],
            "title" => $article_list[0]["title"],
        ];
        // foreach ($article_list as $key => &$value) {
        //     $value["date"] = date("Y-m-d",$value["createtime"]);
        //     unset($value["createtime"]);
        // }
        return  $temp;
    }

    /**
     * 获取宿舍检查结果
     */
    public function getCheckResult($XH)
    {
        $listInfo = Db::view("dormitory_beds")
                    -> view("dormitory_rooms","LH,SSH,ID","dormitory_rooms.ID = dormitory_beds.FYID")
                    -> view("dormitory_hygiene","*","dormitory_rooms.LH = dormitory_hygiene.LH and dormitory_rooms.SSH = dormitory_hygiene.SSH")
                    -> where("dormitory_beds.XH",$XH)
                    -> select();
        $result = [];
        if (empty($listInfo)) {
            return $result;
        }
        foreach ($listInfo as $key => $value) {
            $result[$key]["remark"] = $value["remark"];
            $result[$key]["id"] = $value["ID"];
            $result[$key]["date"] = date("Y-m-d",$value["time"]);
            $result[$key]["score"] = $value["WSQK"];
        }
        return $result;
    }

}