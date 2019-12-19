<?php

namespace app\api\model;

use think\Model;
use fast\Http;
use think\Db;
use think\Validate;

class Clock extends Model
{
    // 表名
    protected $name = '';

    /**
     * 初始化方法
     */
    public function index($key){
        // $stu_id = "2017902148";
        $open_id = $key['openid'];
        $user_id =  $key["user_id"];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        if (empty($safe)) {
            return ['status' => false, 'msg' => "请先绑定学号"];
        }
        $stu_id = $safe["portal_id"];
        $userInfo = Db::view("stu_detail","YXDM,XH,BJDM")
            ->view("dict_college","YXDM,YXMC","stu_detail.YXDM = dict_college.YXDM")
            ->where("XH",$stu_id)
            ->find();

        if (empty($userInfo)) {
            return ['status' => false, 'msg' => "NEED_PORTAL_LOGIN","data" => []];
        }
        //首先判断当前是否有可以报名的活动
        $activityList = Db::name("clock_activity_list")->where("JSSJ",">=",time())->order("jSSJ asc")->select();
        if (empty($activityList)) {
            //没有可报名的活动
            $data = [
                "clock_status"  =>  [
                    "is_activity"   =>  false,
                    "is_wait"       =>  false,
                ],
            ];
            return ["status"=>true,"msg"=>"当前没有可报名的活动","data" => $data ];
        } 
        $activityList = $activityList[0];
        //判断是否报名
        // $applyInfo = Db::name("clock_apply_list")->where("XH",$userInfo["XH"])->where("HDID",$activityList["ID"])->find();
        $applyInfo = Db::name("clock_apply_list")->where("user_id",$user_id)->where("HDID",$activityList["ID"])->find();
        if (empty($applyInfo)) {            
            $data = [
                "clock_status"  =>  [
                    "is_activity"   =>  true,
                    "is_apply"		=>	false,	//当前用户
                    "is_wait"       =>  true,
                    "activity_id"	=>	$activityList["ID"],//活动ID
                    "start_time"    =>  date("Y-m-d H:i",$activityList["KSSJ"]),
                ],
                "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
                "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
            ];
            return ["status"=>true,"msg"=>"尚未报名活动，请先报名","data"=>  $data ];
        }

        //获取当日打卡开始的日期时间戳
        $startClockTime = strtotime( date("Y-m-d",time())." ".$activityList["DKKSSJ"] );
        //获取当日打卡结束的日期时间戳
        $endClockTime = strtotime( date("Y-m-d",time())." ".$activityList["DKJSSJ"] );
        //获取下次打卡时间，记得判断当前时间是否是活动结束最后一天
        if ( strtotime( date("Y-m-d",time())) == strtotime( date("Y-m-d",$activityList["JSSJ"]) ) ) {
            $nextClockTime = null;
            $timeFarNext = null;
        } else {
            $nextClockTime = strtotime( date( "Y-m-d", strtotime("+1 day") )." ".$activityList["DKKSSJ"] );
            $timeFarNext = gmstrftime("%H:%M:%S",$nextClockTime-time());  
        }

        //判断当前是否到了活动开始日期
        if (time() < $activityList["KSSJ"] ) {

            $startClock = strtotime( date("Y-m-d", $activityList["KSSJ"])." ".$activityList["DKKSSJ"] );
            $hour=floor(($startClock-time())/3600);
            // dump($hour);
            if ($hour >= 24) {
                $timeFar = floor( ($startClock-time()) / (3600*24) );
            } else {
                $timeFar = gmstrftime("%H:%M:%S",$startClock-time());
            }

            $data = [
                "clock_status"	=>	[
                    "is_activity"	=>	true, //当前有活动
                    "is_apply"		=>	true,	//当前用户已经报名
                    "is_dk"			=>	false,  //尚未打卡
                    "dk_start_time"	=>	$timeFar ,//距离打卡开始时间
                    "dk_start_day"  =>  $hour >= 24 ? (int)$timeFar : null,
                    // "dk_start_time"	=>	date("Y-m-d H:i",$startClock),//打卡开始时间
                    "activity_id"	=>	$activityList["ID"],//活动ID
                    "can_dk"		=>	false,//是否可以打卡
                ],
                "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
                "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
            ];
            return ["status"=>true,"msg"=>"success","data"=>  $data ];
        }

        //当前活动开启并且用户也已经报名
        //判断用户是否已经打卡
        $clockInfo  =   Db::name("clock_user_list")
                    // ->where("XH",$userInfo["XH"])
                    ->where("user_id",$user_id)
                    ->where("HDID",$activityList["ID"])
                    ->where("DKSJ",">=",$startClockTime)
                    ->where("DKSJ","<=",$endClockTime)
                    ->find();

        if (empty($clockInfo)) {
            # 当前用户尚未打卡，并且判断当前时间是在打卡开始之前还是打卡中还是打卡时间后
            if (time() < $startClockTime ) {    
                $timeFarStart = gmstrftime("%H:%M:%S",$startClockTime-time());  
                $timeFarEnd = gmstrftime("%H:%M:%S",$endClockTime-time());  
                $data = [
                    "clock_status"	=>	[
                        "is_activity"	=>	true, //当前有活动
                        "is_apply"		=>	true,	//当前用户已经报名
                        "is_dk"			=>	false,  //尚未打卡
                        // "dk_start_time"	=>	date("H:i",$startClockTime),//打卡开始时间
                        "dk_start_time"	=>	$timeFarStart,//打卡开始时间
                        // "dk_end_time"	=>	date("H:i",$endClockTime),//打卡结束时间
                        "dk_end_time"	=>	$timeFarEnd,//打卡结束时间
                        "activity_id"	=>	$activityList["ID"],//活动ID
                        "can_dk"		=>  false,//是否可以打卡
                    ],
                    "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
                    "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
                ];
            } elseif (time() >= $startClockTime && time() <= $endClockTime ) {
                $timeFarEnd = gmstrftime("%H:%M:%S",$endClockTime-time());  
                $data = [
                    "clock_status"	=>	[
                        "is_activity"	=>	true, //当前有活动
                        "is_apply"		=>	true,	//当前用户已经报名
                        "is_dk"			=>	false,  //尚未打卡
                        // "dk_start_time"	=>	date("H:i",$startClockTime),//打卡开始时间
                        // "dk_end_time"	=>	date("H:i",$endClockTime),//打卡结束时间
                        "dk_end_time"	=>	$timeFarEnd,//打卡结束时间
                        "activity_id"	=>	$activityList["ID"],//活动ID
                        "can_dk"		=>  true,//是否可以打卡
                    ],
                    "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
                    "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
                ];
            } elseif (time() > $endClockTime ) {

                $data = [
                    "clock_status"	=>	[
                        "is_activity"	=>	true,   //当前有活动
                        "is_apply"		=>	true,	//当前用户已经报名
                        "is_dk"			=>	false,  //尚未打卡
                        // "dk_start_time"	=>	date("H:i",$nextClockTime),//打卡开始时间
                        "dk_start_time"	=>	$timeFarNext ,//打卡开始时间
                        "activity_id"	=>	$activityList["ID"],//活动ID
                        "can_dk"		=>  false,//是否可以打卡
                    ],
                    "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
                    "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
                ];
            }
            return ["status"=>true,"msg"=>"success","data"=>  $data ];
        }

        //当前用户已经打卡
        $data = [
            "clock_status"	=>	[
                "is_activity"	=>	true, //当前有活动
                "is_apply"		=>	true,	//当前用户
                "is_dk"			=>	true,
                "activity_id"	=>	$activityList["ID"],//活动ID
                "dk_start_time"	=>	$timeFarNext,//下次打卡的时间
            ],
            "personal_info" =>  $this->getPersonalInfo($user_id,$activityList["ID"])["data"],
            "rank_info"     =>  $this->getRankInfo($activityList["ID"])["data"],
        ];
        return ["status"=>true,"msg"=>"success","data"=>  $data ];
        
    }

    /**
     * 获取个人基本信息
     * @param int user_id
     * @param int activity_id
     */
    public function getPersonalInfo($user_id,$ID)
    {
        $returnData = Db::name("clock_apply_list")->where("user_id",$user_id)->where("HDID",$ID)->find();
        $userInfo   = Db::name("wx_user")->where("id",$user_id)->find();
        if (empty($returnData)) {
            return ["status"=>false,"msg"=>"error","data" => []];
        } else {
            $data   = [
                "avatarUrl"             =>  $userInfo["avatar"],
                "nickName"              =>  $userInfo["nickname"],
                "running_days"			=>	$returnData["LXTS"],//连续打卡天数
			    "total_activity_days"	=>	$returnData["LJTS"],//活动期间内累计打卡天数
			    // "total_days"	        =>  "",
            ];
            return ["status"=>true,"msg"=>"success","data" => $data];
        }
    }

    /**
     * 获取排名信息
     * @param int activity_id 
     */
    public function getRankInfo($ID)
    {
        //获取当天打卡时间前十
        $todayList = Db::view("clock_user_list")
                // ->view("stu_detail","XH,XM,YXDM,BJDM","clock_user_list.XH = stu_detail.XH")
                ->view("wx_user","nickname,avatar","clock_user_list.user_id = wx_user.id")
                // ->view("dict_college","YXMC,YXDM","stu_detail.YXDM = dict_college.YXDM")
                ->where("DKSJ",">=",strtotime( date("Y-m-d",time()) ))
                ->where("HDID",$ID)
                ->order("DKSJ desc")
                ->limit(10)
                ->select();

        $todayResult = [];
        $i = 1;
        foreach ($todayList as $k => $v) {
            $temp = [
                "rank"  =>  $i,
                "XM"    =>  $v["nickname"],
                // "XH"    =>  $v["XH"],
                "nickName"  =>  $v["nickname"],
                "avatar"    =>  $v["avatar"],
                "LXTS"  =>  Db::name("clock_apply_list")->where("user_id",$v["user_id"])->where("HDID",$v["HDID"])->field("LXTS")->find()["LXTS"],
                // "YXMC"  =>  $v["YXMC"],
                "DKSJ"  =>  date("H:i",$v["DKSJ"]),
            ];
            $i++;
            $todayResult[] = $temp;
        }

        //当前活动累积打卡次数排名
        $totalActivityList = Db::view("clock_apply_list")
                // ->view("stu_detail","XH,XM,YXDM,BJDM","clock_apply_list.XH = stu_detail.XH")
                ->view("wx_user","avatar,nickname","clock_apply_list.user_id = wx_user.id")
                // ->view("dict_college","YXMC,YXDM","stu_detail.YXDM = dict_college.YXDM")
                ->where("HDID",$ID)
                ->order("LJTS desc")
                ->where("LJTS",">","0")
                ->select();
        //当前活动连续打卡次数排名
        // $runningList = Db::view("clock_apply_list")
        //         ->view("wx_user","avatar,nickname","clock_apply_list.user_id = wx_user.id")
        //         ->where("HDID",$ID)
        //         ->order("LXTS desc")
        //         ->where("LXTS",">","0")
        //         ->select();
        $totalActivityResult = [];
        $i = 1;
        foreach ($totalActivityList as $k => $v) {
            $temp = [
                "rank"      =>  $i,
                "XM"        =>  $v["nickname"],
                "nickName"  =>  $v["nickname"],
                "avatar"    =>  $v["avatar"],
                "LJTS"      =>  $v["LJTS"],
                "LXTS"      =>  $v["LXTS"],
            ];
            $i++;
            $totalActivityResult[] = $temp;
        }     

        
        return [
            "status"    =>  true,
            "msg"       =>  "success",
            // "data"      =>  [
            //     "today_list"    =>  $todayResult,
            //     "total_list"    =>  $totalActivityList,
            //     "runnging_list" =>  $runningList,
            // ],
            "data"      =>  [ "0"=> $todayResult,"1" => $totalActivityResult],
        ];
    }

    /**
     * 报名
     * @param int open_id
     * @param int activity_id
     */
    public function apply($key)
    {
        $user_id = $key["user_id"];
        $open_id = $key['openid'];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        if (empty($safe)) {
            return ['status' => 'false', 'msg' => "请求非法"];
        }
        $stu_id = $safe["portal_id"];
        $userInfo = Db::view("stu_detail","YXDM,XH,BJDM")
            ->view("dict_college","YXDM,YXMC","stu_detail.YXDM = dict_college.YXDM")
            ->where("XH",$stu_id)
            ->find();
        // dump($NJDM);
        if (empty($userInfo)) {
            return ['status' => false, 'msg' => "NEED_PORTAL_LOGIN","data" => []];
        }
        //获取活动id
        $activity_id = 0;
        $activityInfo = $this->index($key);
        if (!empty($activityInfo["data"]["clock_status"]["activity_id"])) {
            $activity_id = $activityInfo["data"]["clock_status"]["activity_id"];
        }
        //当前时间段是否在对应活动内
        $activityList = Db::name("clock_activity_list")
                ->where("KSSJ","<=",time())
                ->where("JSSJ",">=",time())
                ->where("ID",$activity_id)
                ->find();
        if (empty($activityList)) {
            return ['status' => false, 'msg' => "当前活动未开始，不可以报名哝！","data" => []];
        }
        //判断用户是否报名
        $check = Db::name("clock_apply_list")->where("user_id",$user_id)->where("HDID",$activity_id)->find();
        if(!empty($check)){
            return ['status' => false, 'msg' => "不可重复报名","data" => []];
        }
    
        $insertData = [
            // "XH"    =>  $stu_id,
            "user_id"   =>  $user_id,
            "HDID"  =>  $activity_id,
            "BMSJ"  =>  time(),
            "LJTS"  =>  0,
            "LXTS"  =>  0,
            "maxLXTS"=> 0,
        ];
        $res = Db::name("clock_apply_list")->insert($insertData);
        if ($res) {
            return ['status' => true, 'msg' => "报名成功","data" => [ "apply_info"  => $insertData]];
        }
        return ['status' => false, 'msg' => "请稍后再试","data" => []];
    }


     /**
     * 打卡
     * @param int open_id
     * @param int activity_id
     */
    public function clock($key)
    {
        $open_id = $key['openid'];
        $user_id = $key["user_id"];
        $safe = Db::name('wx_user') -> where('open_id',$open_id) -> field('portal_id') -> find();
        if (empty($safe)) {
            return ['status' => 'false', 'msg' => "请求非法"];
        }
        $stu_id = $safe["portal_id"];
        $userInfo = Db::view("stu_detail","YXDM,XH,BJDM")
            ->view("dict_college","YXDM,YXMC","stu_detail.YXDM = dict_college.YXDM")
            ->where("XH",$stu_id)
            ->find();

        if (empty($userInfo)) {
            return ['status' => false, 'msg' => "NEED_PORTAL_LOGIN","data" => []];
        }
        //获取活动id
        $activity_id = 0;
        $activityInfo = $this->index($key);
        if (!empty($activityInfo["data"]["clock_status"]["activity_id"])) {
            $activity_id = $activityInfo["data"]["clock_status"]["activity_id"];
        }

        //当前时间段是否在对应活动内
        $activityList = Db::name("clock_activity_list")
                ->where("KSSJ","<=",time())
                ->where("JSSJ",">=",time())
                ->where("ID",$activity_id)
                ->find();
        if (empty($activityList)) {
            return ['status' => false, 'msg' => "活动已结束","data" => []];
        }
        //判断用户是否报名

        $check = Db::name("clock_apply_list")
                ->where("user_id",$user_id)
                ->where("HDID",$activity_id)
                ->find();

        if(empty($check)){
            return ['status' => false, 'msg' => "请先报名，方可打卡","data" => []];
        }
        //判断用户今天是否打卡
        //获取当日打卡开始的日期时间戳
        $startClockTime = strtotime( date("Y-m-d",time())." ".$activityList["DKKSSJ"] );
        //获取当日打卡结束的日期时间戳
        $endClockTime = strtotime( date("Y-m-d",time())." ".$activityList["DKJSSJ"] );
        //获取昨天打卡开始时间
        $startLastClockTime = strtotime( date("Y-m-d",strtotime("-1 day"))." ".$activityList["DKKSSJ"] );
        //获取昨天打开结束时间
        $endLastClockTime =   strtotime( date("Y-m-d",strtotime("-1 day"))." ".$activityList["DKJSSJ"] );

       
        //判断当前是否在打卡时间内
        if (time() < $startClockTime || time() > $endClockTime) {
            return ['status' => false, 'msg' => "当前时间不可以打卡！","data" => []];
        }
        $checkClock = Db::name("clock_user_list")
                ->where("user_id",$user_id)
                ->where("DKSJ",">=",$startClockTime)
                ->where("DKSJ","<=",$endClockTime)
                ->find();
        if (!empty($checkClock)) {
            return ['status' => false, 'msg' => "今天已经打过卡了哟！","data" => []];
        }
        
        // 启动事务
        $updateFlag = false;
        $insertFlag = false;
        Db::startTrans();            
        try{
            //判断昨天是否打卡
            $checkLastClock = Db::name("clock_user_list")
                        ->where("user_id",$user_id)
                        ->where("HDID",$activity_id)
                        ->where("DKSJ",">=",$startLastClockTime)
                        ->where("DKSJ","<=",$endLastClockTime)
                        ->find();

            if (empty($checkLastClock)) {
                $updateFlag1 = Db::name("clock_apply_list")
                    ->where("user_id",$user_id)
                    ->where("HDID",$activity_id)
                    ->update([ "LXTS" => 1]);
            } else{
                $updateFlag1 = Db::name("clock_apply_list")
                    ->where("user_id",$user_id)
                    ->where("HDID",$activity_id)
                    ->setInc("LXTS", 1);
            }

            $updateFlag = Db::name("clock_apply_list")
                    ->where("user_id",$user_id)
                    ->where("HDID",$activity_id)
                    ->setInc("LJTS",1);
            
            $insertData = [
                "user_id"    =>  $user_id,
                "HDID"  =>  $activity_id,
                "DKSJ"  =>  time(),
            ];
            $insertFlag = Db::name("clock_user_list")->insert($insertData);
            // 提交事务
            Db::commit();  
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
       
        if ($insertFlag && $updateFlag) {
            return ['status' => true, 'msg' => "打卡成功","data" => []];
        }
        return ['status' => false, 'msg' => "请稍后再试","data" => []];
    }


}