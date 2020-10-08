<?php

namespace app\admin\model\record;

use think\Model;
use think\Db;
use app\admin\model\record\RecordStuinfo as RecordStuinfoModel;
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
        $RecordStuinfoModel = new RecordStuinfoModel();
        $stuInfo = $RecordStuinfoModel -> get($ID);
        $stuExtraInfo = Db::name("stu_detail") -> where("XH",$stuInfo["XH"])->find();
        // $stuFamilyInfo = Db::name("fresh_family_info")
        $stuFamilyInfo = Db::name("fresh_questionnaire_family")
                        -> where("XH",$stuInfo["XH"])
                        -> select();

        return [ "stuInfo" => $stuInfo, "familyInfo" => $stuFamilyInfo,"stuExtraInfo" => $stuExtraInfo];
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
        return $first_insert_res&&$second_insert_res;
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

    //表关联获取本人谈话记录
    public function getcontent()
    {
        return $this->belongsTo('RecordStuinfo', 'XSID')-> setEagerlyType(0);
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
    /*
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
    /* 
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
    */
     /**
     * 获取最近12个月的图表统计信息
     * @return  { "label":[1,2,3,4,5],"stuCount":[1,0,1,0],"numCount":[1,1,0,1]}
     */
    public function getChartData($adminId)
    {
        $num = 12;
        $monthArray = array();
        for ($i = 0; $i < $num ; $i++) { 
            $temp1 =  date("Y-n",strtotime("-$i month",strtotime("today")));
            // $keyMap = $num-$i;
            $monthArray[$i] = $temp1;
        }
        $monthArray = array_reverse($monthArray);
        //每月谈话学生数
        $monthStuArrayMap  = array();
        //每月谈话次数
        $monthNumArrayMap  = array();
        $length = count($monthArray);

        for ($i=0; $i < $length - 1; $i++) {
            $startTimestamp = strtotime($monthArray[$i]);
            $endTimestamp = strtotime($monthArray[$i+1]);
            $allTalkWeekCount = Db::view("record_stuinfo","XH,XM")
                                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                -> where("admin_id",$adminId)
                                -> where("THSJ",'>',$startTimestamp)
                                -> where("THSJ",'<',$endTimestamp)
                                -> count();
                                // dump($allTalkWeekCount);
            //当月谈话学生数量
            $allTalkWeekStuCount = Db::view("record_stuinfo","XH,XM")
                                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                -> group("XSID")
                                -> where("admin_id",$adminId)
                                -> where("THSJ",'>',$startTimestamp)
                                -> where("THSJ",'<',$endTimestamp)
                                -> count();
                                // dump($allTalkWeekStuCount);
            // $mapKey = $num-$key-1;
            $monthStuArrayMap[$i] = $allTalkWeekStuCount;
            $monthNumArrayMap[$i] = $allTalkWeekCount;
        }

        $monthStuArrayMap[$length - 1] = Db::view("record_stuinfo","XH,XM")
                                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                            -> where("admin_id",$adminId)
                                            -> where("THSJ",'>',strtotime(date("Y-n",strtotime("today"))))
                                            -> where("THSJ",'<',time())
                                            -> count();
        $monthNumArrayMap[$length - 1] = Db::view("record_stuinfo","XH,XM")
                                            -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                            -> group("XSID")
                                            -> where("admin_id",$adminId)
                                            -> where("THSJ",'>',strtotime(date("Y-n",strtotime("today"))))
                                            -> where("THSJ",'<',time())
                                            -> count();  
        $result = [
            "label" => $monthArray,
            "numCount" => $monthNumArrayMap,
            "stuCount" => $monthStuArrayMap,
        ];
        return $result;
        
    }
     /**
     * 获取本周之前三周的统计信息
     * @return  { "label":[5.13-5.19,5.20-5.26,5.27-6.2,6.3-6.7],"stuCount":[1,0,1,0],"numCount":[1,1,0,1]}
     */
    /*
    public function getChartData($adminId)
    {
        
        $num = 12;
        $weekArray = array();
        for ($i = 0; $i < $num ; $i++) { 
            $temp1 =  date("n-j",strtotime("-$i week",strtotime("this week")));
            $temp2 =  date("n-j",strtotime("-$i week",strtotime("this sunday")));
            // $keyMap = $num-$i;
            $weekArray[$i] = "$temp1~$temp2";
        }
        $weekArray = array_reverse($weekArray);
        //每天谈话学生数
        $weekStuArrayMap  = array();
        //每天谈话次数
        $weekNumArrayMap  = array();
        //累积谈话次数
        $allCountArray   = array();
        foreach ($weekArray as $key => $value) {
            $weekDay = explode("~",$value);
            $startTimestamp = strtotime(date('Y')."-".$weekDay[0]);
            $endTimestamp = strtotime(date('Y')."-".$weekDay[1])+86400;
            $allTalkWeekCount = Db::view("record_stuinfo","XH,XM")
                                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                -> where("admin_id",$adminId)
                                -> where("THSJ",'>',$startTimestamp)
                                -> where("THSJ",'<',$endTimestamp)
                                -> count();
                                // dump($allTalkWeekCount);
            //当周谈话学生数量
            $allTalkWeekStuCount = Db::view("record_stuinfo","XH,XM")
                                -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                                -> group("XSID")
                                -> where("admin_id",$adminId)
                                -> where("THSJ",'>',$startTimestamp)
                                -> where("THSJ",'<',$endTimestamp)
                                -> count();
                                // dump($allTalkWeekStuCount);
            // $mapKey = $num-$key-1;
            //获取累积谈话次数
            $allTalkCount = Db::view("record_stuinfo","XH,XM")
                        -> view("record_content","XSID,THNR,THSJ","record_stuinfo.ID = record_content.XSID")
                        -> where("admin_id",$adminId)
                        -> where("THSJ",'<',$endTimestamp)
                        -> count();


            $weekStuArrayMap[$key] = $allTalkWeekStuCount;
            $weekNumArrayMap[$key] = $allTalkWeekCount;
            $allCountArray[$key] = $allTalkCount;
        }
       
        $result = [
            "label" => $weekArray,
            "numCount" => $weekNumArrayMap,
            "stuCount" => $weekStuArrayMap,
            "allCount" => $allCountArray,
        ];
        return $result;
        
    }
    */
    /**
     * 获取班级统计信息
     */
    public function getChartClassData($adminId)
    {
        

        //keyArray用来存放图表信息
        $keyArray = array();
        $classResult = Db::view("record_stuinfo","XH,XM,THCS")
                        -> view("stu_detail","BJDM,XH","stu_detail.XH = record_stuinfo.XH")
                        -> where("admin_id",$adminId)
                        -> select();
        //班级谈话学生数量
        $keyNumArray = [];
        $keyStuArray = [];
        $resultKeyArray = [];
        foreach ($classResult as $key => $value) {
            if (!empty($value["BJDM"])) {   
                $flag = in_array($value["BJDM"],$keyArray);
                if ($flag == false) {
                    $keyArray[] = (int)$value["BJDM"];
                    $keyNumArray[$value["BJDM"]] = $value["THCS"];
                    $keyStuArray[$value["BJDM"]] = 1;
                } else {
                    $keyNumArray[$value["BJDM"]] = $keyNumArray[$value["BJDM"]] + $value["THCS"];
                    $keyStuArray[$value["BJDM"]]++;
                }
            }
        }
        //排序
        asort($keyArray);
        ksort($keyNumArray);
        ksort($keyStuArray);
        foreach ($keyArray as $key => $value) {
            $resultKeyArray[] = (string)$value;
        }
       
        $result = [
            "label" => $resultKeyArray,
            "numCount" => $keyNumArray,
            "stuCount" => $keyStuArray,
        ];
        return $result;
        
    }
    /**
     * 获取标签统计信息
     */
    public function getChartTagsData($adminId)
    {
        

        //keyArray用来存放图表信息
        $keyArray = array();
        $tagsAll = Db::name("record_tags") -> select();
        //标签谈话次数
        $keyNumArray = [];
        //标签学生数
        $keyStuArray = [];

        foreach ($tagsAll as $key => $value) {
            $value = $value["name"];
            $tempStuCount = Db::name("record_stuinfo")
                        -> where("tags","LIKE","%$value%")
                        -> where("admin_id",$adminId)
                        -> count();
            if ($tempStuCount != 0) {
                $keyArray[] = $value;
                $keyStuArray[] = $tempStuCount;
                $tempNumCount = Db::name("record_stuinfo")
                            -> where("tags","LIKE","%$value%")
                            -> where("admin_id",$adminId)
                            -> sum("THCS");
                $keyNumArray[] = $tempNumCount;
            }
        }

        $result = [
            "label" => $keyArray,
            "numCount" => $keyNumArray,
            "stuCount" => $keyStuArray,
        ];
        return $result;
        
    }
}
