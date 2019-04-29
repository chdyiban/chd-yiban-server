<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class RecordContent extends Model
{
    // 表名
    protected $name = 'record_content';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    
    public function getStuInfo($ID)
    {
        $stuInfo = model("RecordStuinfo") -> get($ID);
        return $stuInfo;
    }

    //添加内容
    /**
     * add新增数据
     */
    public function insertContent($param,$adminId)
    {

        $insertData = [
            "THSJ" => strtotime($param['THSJ']),
            "THNR" => $param['THNR'],
            "XSID" => $param['ID'],
        ];

        $first_insert_res = $this->insert($insertData);
        $second_insert_res = Db::name("record_stuinfo") -> where("ID",$param["ID"]) -> setInc('THCS');
        $third_insert_res = $this->UpdateLatestTime($param["ID"]);
        return $first_insert_res&&$second_insert_res&&$third_insert_res;
    }

    //更新最近谈话时间
    public function UpdateLatestTime($XSID)
    {
        //将最近更新时间存至stuinfo表
        $resultSelect = Db::name("record_content") -> where("XSID",$XSID) -> order("THSJ desc")-> select();
        if (!empty($resultSelect )) {
            $resulUpdate = Db::name("record_stuinfo") -> where("ID",$XSID) -> update(["THSJ" => $resultSelect[0]["THSJ"]]);
        } else {
            $resulUpdate = true;
        }
        return $resulUpdate;
    }

    //获取本人最近的谈话记录
    public function getTableData($adminId)
    {
        $info = Db::view("record_stuinfo","XH,XM")
                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                -> where("admin_id",$adminId)
                -> order("record_content.THSJ desc")
                -> limit(10)
                -> select();
        // dump($info); 
        // foreach ($college as $key => $value) {
        //     $college_id = $value['YXDM'];
        //     $finished_num = $this -> where('YXDM', $college_id) -> where('status', 'finished') -> count();
        //     $college[$key]['finished_num'] = $finished_num;
        //     $college[$key]['rest_num'] = $value['bed_num'] - $finished_num;
        // }
        return ['data' => $info, 'count' => count($info)];
    }
    //获取管理员谈话统计信息
    public function getCountParam($adminId)
    {
        //累积谈话次数
        $allTalkCount = Db::view("record_stuinfo","XH,XM")
                        -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                        -> where("admin_id",$adminId)
                        -> count();
        //累积谈话学生数
        $allTlakStuCount = Db::name("record_stuinfo") -> where("admin_id",$adminId)->count();
        //本月累积谈话次数
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
        $allTalkMonthCount = Db::view("record_stuinfo","XH,XM")
                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                            -> where("admin_id",$adminId)
                            -> where("record_stuinfo.THSJ",'>=',$beginThismonth)
                            -> count();
        //本月累积谈话学生
        $allTalkMonthStuCount = Db::view("record_stuinfo","XH,XM")
                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                            -> group("XSID")
                            -> where("admin_id",$adminId)
                            -> where("record_stuinfo.THSJ",'>=',$beginThismonth)
                            -> count();

        $array = [
            "allTalkCount"         =>  $allTalkCount,
            "allTalkStuCount"      =>  $allTlakStuCount,
            "allTalkMonthCount"    =>  $allTalkMonthCount,
            "allTalkMonthStuCount" =>  $allTalkMonthStuCount,
        ];
        return $array;
    }
}
