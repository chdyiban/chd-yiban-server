<?php

namespace app\api\controller\datav;

use app\common\controller\Api;
use think\Db;
use think\Config;
use fast\Http;

use app\api\model\Adviser as AdviserModel;

/**
 * 数据可视化
 */
class Index extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $startTime = 1599440400;

    public $baiduAPI = "http://api.map.baidu.com/geocoding/v3/";    

    public function college()
    {
        $collegeList = Db::view("fresh_result")
                -> view("dict_college","YXMC,YXDM","fresh_result.YXDM = dict_college.YXDM")
                -> group("fresh_result.YXDM")
                -> where("fresh_result.SDSJ",">=",$this->startTime)
                -> column("YXJC,COUNT(*),dict_college.YXDM");
        $collegeOldList = Db::view("fresh_dormitory_back")
                -> view("dict_college","YXMC,YXDM","fresh_dormitory_back.YXDM = dict_college.YXDM")
                -> group("fresh_dormitory_back.YXDM")
                -> order("sum(SYRS) desc")
                -> column("YXJC,fresh_dormitory_back.YXDM,sum(SYRS)");
        // SELECT `YXDM`,COUNT(*) FROM `fa_fresh_result` GROUP BY `YXDM`
        $return = array_keys($collegeOldList);
        $data = [];
        $collegeList["马克思主义学院"] = empty($collegeList["马克思主义学院"]) ? 0 : $collegeList["马克思主义学院"];
        $collegeList["马院"] = $collegeList["马克思主义学院"];
        $collegeOldList["马院"] = $collegeOldList["马克思主义学院"];
        unset($collegeList["马克思主义学院"]);
        unset($collegeOldList["马克思主义学院"]);
        $collegeList["长安都柏林国际交通学院"] = empty($collegeList["长安都柏林国际交通学院"]) ? 0 : $collegeList["长安都柏林国际交通学院"];
        $collegeList["国际学院"] = $collegeList["长安都柏林国际交通学院"];
        $collegeOldList["国际学院"] = $collegeOldList["长安都柏林国际交通学院"];
        unset($collegeList["长安都柏林国际交通学院"]);
        unset($collegeOldList["长安都柏林国际交通学院"]);
        foreach ($return as $key => $value) {
            if($value == "马克思主义学院"){
                $value = "马院";
            }
            if($value == "长安都柏林国际交通学院"){
                $value = "国际学院";
            }
            $YXDM = $collegeOldList[$value]["YXDM"];
            $startTime = strtotime(Config::get("dormitory.$YXDM"));
            $nowTime = time();
            $number  = !empty($collegeList[$value]["COUNT(*)"])?$collegeList[$value]["COUNT(*)"]:0;
            // if ($nowTime >= $startTime) {
                $temp = [
                    // "content" => $value,
                    // "value"   => $collegeList[$value], 
                    "x" => $value,
                    "y"   => $number, 
                    "s" =>  "2",
                ];
                $temp2 = [
                    // "content" => $value,
                    // "value"   => $collegeList[$value], 
                    "x" => $value,
                    "y"   => $collegeOldList[$value]["sum(SYRS)"] - $number, 
                    "s" =>  "1",
                ];
                $data[] = $temp;
                $data[] = $temp2;
            // }
        }
        // dump($data);
        return json($data);
    }

    // 已经完成选宿的所有人，气泡图
    public function myMap()
    {
        $mapList= Db::view("fresh_result","XH")
                -> view("fresh_info","XH,XM,longitude,latitude","fresh_result.XH = fresh_info.XH")
                -> where("fresh_result.SDSJ",">=",$this->startTime)
                -> order("fresh_info.ID desc")
                -> select();
        $result = [];
        foreach ($mapList as $key => $value) {
            $temp = [
                "lng"   => $value["longitude"],
                "lat"   => $value["latitude"],
                "value" => 1,
            ];
            $result[] = $temp;
        }
        return json_encode($result);
    }
    /**
     * 查看最近选宿的学生
     * 流式气泡层
     * 
     */
    public function getStuMap()
    {
        $mapList= Db::view("fresh_result","XH")
                ->view("fresh_info","XH,XM,SYD,longitude,latitude","fresh_info.XH = fresh_result.XH")
                -> where("fresh_result.SDSJ",">=",$this->startTime)
                ->limit(10)
                ->order("fresh_info.ID desc")
                ->select();
        $result = [];
        foreach ($mapList as $key => $value) {
            $temp = [
                "lng"   => $value["longitude"],
                "lat"   => $value["latitude"],
                "info" => $value["XM"]."-".$value["SYD"],
            ];
            $result[] = $temp;
        }
        return json_encode($result);
    }
    /**
     * 调用百度api获取经纬度信息
     */
    private function getMapInfo($value)
    {
        $body = [ 
            "address"   =>  $value,
            "output"    =>  "json",
            // "callback"  =>  "showLocation",
            "ak"        =>  "B8IRbrz1aQ5RsSvb60zLwOghdHUKyUf9",
        ];
        $result = json_decode(Http::Get($this->baiduAPI,$body),true);
        if (isset($result["status"]) && $result["status"] == 0) {
            return $result;
        } else {
            $result = ["result" => ["location" => ["lng" => 108.911429,"lat" => 34.376742 ]]];
            return $result;
        }
    }
    /**
     * 流线图分布
     */
    public function myFloatMap()
    {
        $mapList= Db::view("fresh_result","XH")
                ->view("fresh_info","XH,XM,longitude,latitude","fresh_result.XH = fresh_info.XH")
                -> where("fresh_result.SDSJ",">=",$this->startTime)
                ->order("fresh_info.ID desc")
                ->select();
        $result = [];
        foreach ($mapList as $key => $value) {
            $temp = [
                "from" => $value["longitude"].",".$value["latitude"],
                "to"   => "108.911429,34.376742",
                "value" => 1,
            ];
            $result[] = $temp;
        }
        return json_encode($result);
    }
    /**
     * 查看已经上墙学生经纬度分布
     */
    public function map()
    {
        $maplist= Db::view("fresh_result","XH")
                    -> view("fresh_info","XH,XM,latitude,longitude","fresh_info.XH = fresh_result.XH")
                    -> view("dict_college","YXDM,YXMC","fresh_info.YXDM = dict_college.YXDM")
                    -> where("fresh_result.SDSJ",">=",$this->startTime)
                    
                    -> where("latitude","<>","")
                    -> select();
        $i = 0;
        $data = [];
        foreach ($maplist as $key => $value) {
            $temp = [
                "id"   =>  $i,
                "lng"  =>  $value["longitude"],
                "lat"  =>  $value["latitude"],
                "value"=>   1,
                "info" =>  $value["XM"]."(".$value["YXMC"].")",
            ];

            $i = $i+1;
            $data[] = $temp;
        }
		return json($data);
		// return json([]);		
    }

    /**
     * 查看各省分布人数
     */
    public function mapHeat()
    {
        $cityMap = [
            "北京"   =>  "110000",
            "天津"   =>  "120000",
            "河北"   =>  "130000",
            "山西"   =>  "140000",
            "内蒙古" =>  "150000",
            "辽宁"   =>  "210000",
            "吉林"   =>  "220000",
            "黑龙江" =>  "230000",
            "上海" =>  "310000",
            "江苏" =>  "320000",
            "浙江" =>  "330000",
            "安徽" =>  "340000",
            "福建" =>  "350000",
            "江西" =>  "360000",
            "山东" =>  "370000",
            "河南" =>  "410000",
            "湖北" =>  "420000",
            "湖南" =>  "430000",
            "广东" =>  "440000",
            "广西" =>  "450000",
            "海南" =>  "460000",
            "重庆" =>  "500000",
            "四川" =>  "510000",
            "贵州" =>  "520000",
            "云南" =>  "530000",
            "西藏" =>  "540000",
            "陕西" =>  "610000",
            "甘肃" =>  "620000",
            "青海" =>  "630000",
            "宁夏" =>  "640000",
            "新疆" =>  "650000",
            "台湾" =>  "710000",
            "香港" =>  "810000",
            "澳门" =>  "820000",
        ];
        $resultList = Db::view("fresh_result","status,XH,SDSJ")
                    -> view("fresh_info","SYD,XH","fresh_result.XH = fresh_info.XH")
                    -> where("fresh_result.SDSJ",">=",$this->startTime)
                    -> where("status","finished")
                    -> group("SYD")
                    -> column("SYD,count(*)");
        $result = [];
        if (empty($resultList)) {
            return json($result);
        }
        foreach ($resultList as $key => $value) {
            $temp = [
                "area_id" => $cityMap[$key],
                "value"   => $value,
            ];
            $result[] = $temp;
        }
        return json($result);
    }

    /**
     * 选宿舍情况时段统计
     */
    public function timeCount()
    {
        // $start = date("m-d H:i",1534204800);
        // $end   = date("m-d H:i",1534496400);
        // $start = 1534204800;
        $start = Db::name("fresh_result")->min("SDSJ");
        // $end   = 1534251600;
        // $end   = Db::name("fresh_result")->max("SDSJ");
        // $end    = time();
        $end    = "1597828080";
        $inside = (int)($end - $start) / 10;

        //每小时日期
        $hoursArray  = array();
        //每小时选宿舍人数
        $hoursStuArray  = array();

        $returnData = [];
        $keyMap = array();
        //循环获取每个小时的日期
        for ($i = $start; $i <= $end; $i=$i+$inside) { 
        // for ($i = $start; $i <= $end; $i=$i+3600) { 
            // $hoursArray[] = date("m-d H:i",$i);
            $key = date("Y/m/d H:i:s",$i);
            // $hoursArray[] = $i;
            // $keyMap[$key] = count($hoursArray)-1;
            $hoursStuArray[$key] = 0;
            $keyMap[] = $i;
        }
        //选宿舍结果
        // $stuResultArray = Db::name("fresh_result")
        //             -> field("SDSJ")
        //             -> where("SDSJ",'>=',$start)
        //             -> where("SDSJ",'<=',$end)
        //             -> select();
        $length =  count($keyMap)-1;
        for ($i=0; $i <$length; $i++) { 
            $startDetail = $keyMap[$i];
            $endDetail   = $keyMap[$i+1];
            $count  = Db::name("fresh_result")->where("SDSJ",">=",$startDetail)->where("SDSJ","<=",$endDetail)->count();
            $key = date("Y/m/d H:i:s",$keyMap[$i+1]);
            $hoursStuArray[$key] += $count;
        }
        // foreach ($stuResultArray as $key => $value) {
        //     $SDSJ =  date("Y/m/d H:i:s",$value['SDSJ']);
        //     $hoursStuArray[$SDSJ]++;
        // }
        $allCount = 0;
        foreach ($hoursStuArray as $key => $value) {
            $allCount = $allCount + $value; 
            $temp = [
                "x"  =>  $key,
                // "x"  =>  $key.":00",
                "y"  =>  $value,
                "s"  =>  "1",
            ];
            $tempAll = [
                "x"  =>  $key,
                // "x"  =>  $key.":00",
                "y"  =>  $allCount,
                "s"  =>  "2",
            ];
            $returnData[] = $temp;
            $returnData[] = $tempAll;
        }
        // dump($returnData);
        return json($returnData);


    }

    /**
     * 获取雷达图各指标
     * 1:男生 2:女生
     * 登录，问卷填写，标记床位，选宿，取消，易班报名，推荐
     */
    public function getParam()
    {
        $returnData = [];
        //问卷填写
        $questionBoy = Db::view("fresh_questionnaire_first","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_questionnaire_first.XH")
                        -> where("XBDM","1")
                        -> count();

        $questionGirl = Db::view("fresh_questionnaire_first","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_questionnaire_first.XH")
                        -> where("XBDM","2")
                        -> count();
        //标记床位
        $markBoy = Db::view("fresh_mark","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_mark.XH")
                        -> where("XBDM","1")
                        -> count();
        $markGirl = Db::view("fresh_mark","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_mark.XH")
                        -> where("XBDM","2")
                        -> count();
        //选宿
        $resultBoy = Db::view("fresh_result","XH,SDSJ")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_result.XH")
                        -> where("fresh_result.SDSJ",">=",$this->startTime)
                        -> where("XBDM","1")
                        -> count();
        $resultGirl = Db::view("fresh_result","XH,SDSJ")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_result.XH")
                        -> where("fresh_result.SDSJ",">=",$this->startTime)
                        -> where("XBDM","2")
                        -> count();
        //取消
        $cancelBoy =   Db::view("fresh_cancel","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_cancel.XH")
                        -> where("XBDM","1")
                        -> count();
        $cancelGirl =   Db::view("fresh_cancel","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_cancel.XH")
                        -> where("XBDM","2")
                        -> count();
        //易班报名
        $applyBoy =   Db::view("fresh_apply","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_apply.XH")
                        -> where("XBDM","1")
                        -> count();
        $applyGirl =   Db::view("fresh_apply","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_apply.XH")
                        -> where("XBDM","2")
                        -> count();
        //推荐
        $recommendBoy =   Db::view("fresh_recommend_question","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_recommend_question.XH")
                        -> where("XBDM","1")
                        -> count();
        $recommendGirl =   Db::view("fresh_recommend_question","XH")
                        -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_recommend_question.XH")
                        -> where("XBDM","2")
                        -> count();
        $returnData = [
            [
                "x" => "问卷填写",
                "y" => $questionBoy,
                "s" => "1",
            ],
            [
                "x" => "问卷填写",
                "y" => $questionGirl,
                "s" => "2",
            ],
            [
                "x" => "选宿",
                "y" => $resultBoy,
                "s" => "1",
            ],
            [
                "x" => "选宿",
                "y" => $resultGirl,
                "s" => "2",
            ],
            [
                "x" => "标记床位",
                "y" => $markBoy,
                "s" => "1",
            ],
            [
                "x" => "标记床位",
                "y" => $markGirl,
                "s" => "2",
            ],
            [
                "x" => "取消",
                "y" => $cancelBoy,
                "s" => "1",
            ],
            [
                "x" => "取消",
                "y" => $cancelGirl,
                "s" => "2",
            ],
            [
                "x" => "易班报名",
                "y" => $applyBoy,
                "s" => "1",
            ],
            [
                "x" => "易班报名",
                "y" => $applyGirl,
                "s" => "2",
            ],
            [
                "x" => "推荐",
                "y" => $recommendBoy,
                "s" => "1",
            ],
            [
                "x" => "推荐",
                "y" => $recommendGirl,
                "s" => "2",
            ],
        ];
        return json($returnData);
    }

    /**
     * 获取当前已经选择学生人数
     */
    public function getFinishStu()
    {
        $param = $this->request->get("action");
        $count = Db::name("fresh_result")->where("status","finished")-> where("SDSJ",">=",$this->startTime)->count();
        $allStu = Db::name("fresh_info")->count();
        if ($param == "number") {
            //返回完成人数
            return json([["value" => $count]]);
        } elseif ($param == "text") {
            //返回text完成人数
            return json([["value" => "当前已选".$count."人"]]);
        } elseif ($param == "unfinished") {
            //未完成人数
            return json([["value" =>($allStu-$count)]]);
        } elseif ($param == "todayIncrease") {
            //今日新增
            $today = $this->startTime;
            $todayCount = Db::name("fresh_result")->where("SDSJ",">=",$today)->where("status","finished")->count();
            return json([["value" => $todayCount]]);
        } elseif ($param == "percent") {
            //完成人数百分比
            $percent =  number_format($count/$allStu,4);
            return json([["value" => $percent]]);
        } elseif ($param == "questionnaire") {
            //获取问卷填写总数
            $questionNumber = Db::name("fresh_questionnaire_first")->count();
            return json([["value" => $questionNumber]]);
        } elseif ($param == "chart") {
            $today = $this->startTime;
            $todayCount = Db::name("fresh_result")->where("SDSJ",">=",$today)->where("status","finished")->count();
            $questionNumber = Db::name("fresh_questionnaire_first")->count();
            $result = [
                [
                    "x"  =>  "完成选宿",
                    "y"  =>   $count,
                    "s"  =>  "1",
                ],[
                    "x"  =>  "今日新增",
                    "y"  =>  $todayCount,
                    "s"  =>  "1",
                ], [
                    "x"  =>  "填写问卷",
                    "y"  =>  $questionNumber,
                    "s"  =>  "1",
                ]
            ];
            return json($result);
        }
    }
    public function getSexNumber() {
		// 完成选宿男生数量
		$resultBoy = Db::view("fresh_result","XH,SDSJ")
            -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_result.XH")
            -> where("fresh_result.SDSJ",">=",$this->startTime)
			-> where("XBDM","1")
			-> count();
		// 男生总数
		$resultAllBoy = Db::name("fresh_info")
			-> where("XBDM","1")
			-> count();
		//	完成选宿女生数
		$resultAllGirl = Db::name("fresh_info")
			-> where("XBDM","2")
			-> count();
		$resultGirl = Db::view("fresh_result","XH,SDSJ")
            -> view("fresh_info","XH,XBDM","fresh_info.XH = fresh_result.XH")
            -> where("fresh_result.SDSJ",">=",$this->startTime)
			-> where("XBDM","2")
			-> count();
		$return = [
			[
				"type"		=>	"男生剩余",
				"value"		=>	$resultAllBoy-$resultBoy
            ],
            [
				"type"		=>	"男生完成",
				"value"		=>	$resultBoy,
			],[
				"type"		=>	"女生剩余",
				"value"		=>	$resultAllGirl-$resultGirl,
			],[
				"type"		=>	"女生完成",
				"value"		=>	$resultGirl
			],
		];
		return json($return);
    }
	/**
	 * 获取每栋楼的统计信息
	 */
    
	public function getBuildingCount()
	{
		$buildingOldList = Db::name("fresh_dormitory_back")
						-> group("LH")
						-> order("sum(SYRS) desc")
						-> column("LH,sum(SYRS)");

		$buildingNowList = Db::name("fresh_dormitory_north")
						-> group("LH")
						-> order("sum(SYRS)")
						-> column("LH,sum(SYRS)");
		$returnData = [];
		ksort($buildingOldList);
        foreach ($buildingOldList as $key => $value) {
            $time = time();
            $temp = [
                "x"  => $key."#",
                "y"  => ($value-$buildingNowList[$key]) >= 5 ? ($value-$buildingNowList[$key]) : 0,
                "s"  => "1",
            ];
			$returnData[] = $temp;
		}
		return json($returnData);
	}

	/**
	 * 获取问卷完成情况
	 */
	public function getQuestionCount()
	{
		$questionList = Db::view("fresh_questionnaire_first","XH")
						-> view("fresh_info","YXDM,XH","fresh_questionnaire_first.XH = fresh_info.XH")
						-> view("dict_college","YXJC,YXDM","fresh_info.YXDM = dict_college.YXDM")
						-> group("YXJC")
						-> column("YXJC,count(*)");
		$keyCollege = array_keys($questionList);
		$minuteNow = date("i");
		$returnData = [];
		//返回前十个学院
		if ($minuteNow % 2 == 0) {
			for ($i=0; $i < 10; $i++) { 
				$college = $keyCollege[$i];
				$temp = [
					"x" => $college,
					"y" => $questionList[$college],
				];
				$returnData[] = $temp;
			}
		} else {
			for ($i=10; $i < count($questionList); $i++) { 
				$college = $keyCollege[$i];
				$temp = [
					"x" => $college,
					"y" => $questionList[$college],
				];
				$returnData[] = $temp;
			}
		}

		return  json($returnData);
	}

	/**
	 * 获取推荐统计
	 */
	public function getRecommendCount()
	{
		$param = $this->request->get("action");
		if ($param == "labelCount") {
			return json([["value" => 33 ]]);
		} elseif ($param == "labelContent") {
            $labelList = ["篮球","足球","跑步","二次元","王者荣耀","乐器","文艺","手游","绘画","K歌","佛系","逛街","自拍","星座","桌游","懒癌患者",'美妆', '抖音','直播', '网购', '动漫', '二次元', '正能量', '旅行', '夜猫子', '音乐', '仙气十足','bilibili', '选择恐惧症', '宅男', '追剧', '我爱学习', '吃饱才有力气减肥'];
            // $labelList = ["篮球","足球","跑步","二次元","王者荣耀","乐器","文艺","手游"];
            $label = [];
            foreach ($labelList as $key => $value) {
                $temp = [
                    "name" => $value,
                    "value"=> Db::name("fresh_recommend_question")->where("label","LIKE","%".$value."%")->count()+10,
                    "type" => 0,
                ];
                $label[] = $temp;
            }
			return json($label);
		} elseif ($param == "finishedNumber") {
			$count = Db::name("fresh_recommend_question")->count();
			return json([["value" => $count]]);
		}
	}

	/**
	 * 获取实时数据
	 */
	public function getTimeResult()
	{
		$resultList = Db::view("fresh_result","XH,SSDM,CH,SDSJ")
				-> view("fresh_info","XH,XM,YXDM","fresh_result.XH = fresh_info.XH")
				-> view("dict_college","YXJC,YXDM","fresh_info.YXDM = dict_college.YXDM")
                -> order("fresh_result.ID desc")
                -> where("fresh_result.SDSJ",">=",$this->startTime)
				-> where("status","finished")
				-> limit(10)
				-> select();
        $returnData = [];
		foreach ($resultList as $key => $value) {
            $YXDM = $value["YXDM"];
            $startTime = strtotime(Config::get("dormitory.$YXDM"));
            $nowTime = time();
            if ($nowTime >= $startTime) {
                $temp = [
                    "time"  =>  date("m-n H:i:s",$value["SDSJ"]),
                    "name"  => $value["XM"],
                    "YXMC"  => $value["YXJC"],
                    "SSDM"  => $value["SSDM"],
                    "CH"    => $value["CH"],
                ];
                $returnData[] = $temp;
            }
		}
		return json($returnData);
	}

	/**
	 * 获取床位统计
	 */
	public function getBedCount()
	{
		$param = $this->request->get("action");
        $allBed = Db::name("fresh_dormitory_back")->sum("SYRS");
        $restBed = Db::name("fresh_dormitory_north")->sum("SYRS");
        $return = [
            [
                "x" => "已分配位数",
                "y" => ($allBed-$restBed) >=8 ? $allBed-$restBed : 0,
            ],[
                "x" => "剩余床位数",
                "y" => $restBed,
            ],
        ];
        return json($return);
    }
    /**
     * 获取新填写的问卷
     */
    public function getTimeQuestionnaire()
    {
        $resultList = Db::view("fresh_questionnaire_first","XH")
				-> view("fresh_info","XH,XM,YXDM,SYD","fresh_questionnaire_first.XH = fresh_info.XH")
				-> view("dict_college","YXJC,YXDM","fresh_info.YXDM = dict_college.YXDM")
				-> order("fresh_questionnaire_first.ID desc")
				-> limit(10)
				-> select();
        $returnData = [];
		foreach ($resultList as $key => $value) {
            $YXDM = $value["YXDM"];
            $startTime = strtotime(Config::get("dormitory.$YXDM"));
            $nowTime = time();
            //if ($nowTime >= $startTime) {
            $temp = [
                "time"  =>  date("m-n H:i:s",time()),
                "XH"  => $value["XH"],
                "XM"  => $value["XM"],
                "SYD"  => $value["SYD"],
                "YXMC"  => $value["YXJC"],
            ];
            $returnData[] = $temp;
            //}
		}
		return json($returnData);
    }

    function getUsedStatus(){
        $fp = popen('top -b -n 2 | grep -E "^(%Cpu|KiB Mem|Tasks)"',"r");//获取某一时刻系统cpu和内存使用情况
        $rs = "";
        while(!feof($fp)){
            $rs .= fread($fp,1024);
        }
        pclose($fp);
        $sys_info = explode("\n",$rs);
        $tast_info = explode(",",$sys_info[3]);//进程 数组
         
        $cpu_info = explode(",",$sys_info[4]); //CPU占有量 数组
        $mem_info = explode(",",$sys_info[5]); //内存占有量 数组
        //正在运行的进程数
        $tast_running = trim(trim($tast_info[1],'running'));
        //CPU占有量
        $cpu_usage = trim(trim($cpu_info[0],'%Cpu(s): '),'%us'); //百分比
        //内存占有量
        $mem_total = trim(trim($mem_info[0],'KiB Mem: '),'k total');
        $mem_used = trim($mem_info[1],'k used');
        $mem_usage = round(100*intval($mem_used)/intval($mem_total),2); //百分比

        /*硬盘使用率 begin*/
        $fp = popen('df -lh | grep -E "^(/)"',"r");
        $rs = fread($fp,1024);
        pclose($fp);
        $rs = preg_replace("/\s{2,}/",' ',$rs); //把多个空格换成 “_”
        $hd = explode(" ",$rs);
        $hd_avail = trim($hd[3],'G'); //磁盘可用空间大小 单位G
        $hd_usage = trim($hd[4],'%'); //挂载点 百分比
        //print_r($hd);
        /*硬盘使用率 end*/
        //检测时间
        // $fp = popen("date +\"%Y-%m-%d %H:%M\"","r");
        // $rs = fread($fp,1024);
        // pclose($fp);
        // $detection_time = trim($rs);
        $result = [
            "cpu_usage" =>  $cpu_usage,
            "mem_usage" =>  $mem_usage,
            "hd_avail"  =>  $hd_avail,
            "hd_usage"  =>  $hd_usage,
            'tast_running'=>$tast_running,
        ];

        $param = $this->request->get("action");
        switch ($param) {
            case 'CPU':
                return json(
                    [
                        [
                            "aims" => "100",
                            "actual"    =>  $result["cpu_usage"]
                        ],
                    ]
                );
            case 'CPUNum':
                return json(
                    [
                        [
                            "name" => "CPU使用率",
                            "value"    =>   $result["cpu_usage"],
                        ],
                    ]
                );
            case "Mem":
                return json(
                    [
                        [
                            "aims" => "100",
                            "actual"    =>  $result["mem_usage"],
                        ],
                    ]
                );
            case "MemNum":
                return json(
                    [
                        [
                            "name" => "内存使用率",
                            "value" => $result["mem_usage"],
                        ],
                    ]
                );
            case "Hd":
                return json(
                    [
                        [
                            "aims" => "100",
                            "actual"    =>  $result["hd_usage"],
                        ],
                    ]
                );
            case "HdNum":
                return json(
                    [
                        [
                            "name"  =>  "硬盘使用率",
                            "value" =>  $result["hd_usage"],
                        ]
                    ]
                );
            default:
                return json($result);
        }

    }

}