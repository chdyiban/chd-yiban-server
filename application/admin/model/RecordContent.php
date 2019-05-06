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
    public function getTableData($adminId,$offset,$limit)
    {
        $info = Db::view("record_stuinfo","XH,XM")
                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                -> where("admin_id",$adminId)
                -> order("record_content.THSJ desc")
                -> limit("$offset,$limit")
                -> select();

        $count = Db::view("record_stuinfo","XH,XM")
                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                -> where("admin_id",$adminId)
                -> order("record_content.THSJ desc")
                -> count();
        // dump($info); 
        // foreach ($college as $key => $value) {
        //     $college_id = $value['YXDM'];
        //     $finished_num = $this -> where('YXDM', $college_id) -> where('status', 'finished') -> count();
        //     $college[$key]['finished_num'] = $finished_num;
        //     $college[$key]['rest_num'] = $value['bed_num'] - $finished_num;
        // }
        return ['data' => $info, 'count' => $count];
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
                            -> where("THSJ",'>=',$beginThismonth)
                            -> count();
        //本月累积谈话学生
        $allTalkMonthStuCount = Db::view("record_stuinfo","XH,XM")
                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                            -> group("XSID")
                            -> where("admin_id",$adminId)
                            -> where("THSJ",'>=',$beginThismonth)
                            -> count();

        $array = [
            "allTalkCount"         =>  $allTalkCount,
            "allTalkStuCount"      =>  $allTlakStuCount,
            "allTalkMonthCount"    =>  $allTalkMonthCount,
            "allTalkMonthStuCount" =>  $allTalkMonthStuCount,
        ];
        return $array;
    }

     /**
     * 获取到今天为止的图表统计信息
     * @return  { "label":[5.1,5.2,5.3,5.4],"stuCount":[1,0,1,0],"numCount":[1,1,0,1]}
     */
    /* 改为以月份为统计单位
    public function getChartData($adminId)
    {
        $days = date("d");
        //每月日期
        $daysArray  = array();
        //每天谈话学生数
        $daysStuArrayMap  = array();
        //每天谈话次数
        $daysNumArrayMap  = array();
        $keyMap = array();
        for($i = 1;$i <= $days; $i++){
                $daysArray[] = date("n")."-".$i;
                $daysNumArrayMap[] = 0;
                $daysStuArrayMap[] = 0;
                $keyMap[date("n")."-".$i] = $i-1;
        }
        //本月累积谈话次数
        $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
    
        $allTalkMonthInfo = Db::view("record_stuinfo","XH,XM")
                        -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                        -> where("admin_id",$adminId)
                        -> where("THSJ",'>=',$beginThismonth)
                        -> select();

        foreach ($allTalkMonthInfo as $key => $value) {
            $THSJ = date("n-j",$value['THSJ']);
            $daysNumArrayMap[ $keyMap[$THSJ] ] =$daysNumArrayMap[ $keyMap[$THSJ] ]+1;
        }
        //本月谈话学生
        $allTalkMonthStuInfo = Db::view("record_stuinfo","XH,XM")
                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                            -> group("XSID")
                            -> where("admin_id",$adminId)
                            -> where("THSJ",'>=',$beginThismonth)
                            -> select();
        foreach ($allTalkMonthStuInfo as $key => $value) {
            $THSJ = date("n-j",$value['THSJ']);
            $daysStuArrayMap[ $keyMap[$THSJ] ] = $daysStuArrayMap[ $keyMap[$THSJ] ] + 1;
        }
        $result = [
            "label" => $daysArray,
            "numCount" => $daysNumArrayMap,
            "stuCount" => $daysStuArrayMap,
        ];
        return $result;
    }
    */
    
     /**
     * 获取到本月为止的图表统计信息
     * @return  { "label":[1,2,3,4,5],"stuCount":[1,0,1,0],"numCount":[1,1,0,1]}
     */
    public function getChartData($adminId)
    {
        $month = date("n");
        //每月日期
        $monthsArray  = array();
        //每天谈话学生数
        $monthsStuArrayMap  = array();
        //每天谈话次数
        $monthsNumArrayMap  = array();
        $keyMap = array();
        for($i = 1;$i <= $month; $i++){
                $monthsArray[] = date("Y")."-".$i;
                $monthsNumArrayMap[] = 0;
                $monthsStuArrayMap[] = 0;
                $keyMap[date("Y")."-".$i] = $i-1;
        }
        //本年累积谈话次数
        $beginThisyear = mktime(0,0,0,1,1,date('Y'));
    
        $allTalkMonthInfo = Db::view("record_stuinfo","XH,XM")
                        -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                        -> where("admin_id",$adminId)
                        -> where("THSJ",'>=',$beginThisyear)
                        -> select();

        foreach ($allTalkMonthInfo as $key => $value) {
            $THSJ = date("Y-n",$value['THSJ']);
            $monthsNumArrayMap[ $keyMap[$THSJ] ] =$monthsNumArrayMap[ $keyMap[$THSJ] ]+1;
        }

        //本年谈话学生
        $allTalkMonthStuInfo = Db::view("record_stuinfo","XH,XM")
                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                            -> group("XSID")
                            -> where("admin_id",$adminId)
                            -> where("THSJ",'>=',$beginThisyear)
                            -> select();
        foreach ($allTalkMonthStuInfo as $key => $value) {
            $THSJ = date("Y-n",$value['THSJ']);
            $monthsStuArrayMap[ $keyMap[$THSJ] ] = $monthsStuArrayMap[ $keyMap[$THSJ] ] + 1;
        }
        $result = [
            "label" => $monthsArray,
            "numCount" => $monthsNumArrayMap,
            "stuCount" => $monthsStuArrayMap,
        ];
        return $result;
    }
}
