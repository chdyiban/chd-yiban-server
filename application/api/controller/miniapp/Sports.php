<?php

namespace app\api\controller\miniapp;

use app\common\controller\Api;
use think\Config;
use think\Db;
use \think\Cache;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Sports as SportsModel;
use app\common\library\Token;
/**
 * 运动会接口
 */
class Sports extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const LOGIN_URL = 'https://api.weixin.qq.com/sns/jscode2session';
    const PORTAL_URL = 'http://ids.chd.edu.cn/authserver/login';
    const COLLEGE_PERSON_ARRAY  = [
        //公路学院
        '2100'  =>  '4878',
        //汽车学院
        '2200'  =>  '2541',
        //机械学院
        '2500'  =>  '2708',
        //经管学院
        '2300'  =>  '2948',
        //电控学院
        '3200'  =>  '2387',
        //信息学院
        '2400'  =>  '2971',
        //地测学院
        '2600'  =>  '2737',
        //资源学院
        '2700'  =>  '1042',
        //建工学院
        '2800'  =>  '2931',
        //水环学院
        '2900'  =>  '1441',
        //建筑学院
        '4100'  =>  '1288',
        //材料学院
        '3100'  =>  '1737',
        //运输学院
        '3400'  =>  '817',
        //土地学院
        '3500'  =>  '340',
        //人文学院，马院，，外语学院，理学院，体育系
        '1100'  =>  '1718',
        '1600'  =>  '267',
        '1300'  =>  '282',
        '1200'  =>  '783',
        '1400'  =>  '118',
        //学工部，预科生，长安都柏林国际交通学院
        '0001'  =>  '211',
        '3600'  =>  '361',
        '7100'  =>  '59',
    ];

    /**
     * @param token
     * @param 不加密
     */
    public function index()
    {
        date_default_timezone_set('PRC');
        $SportsModel = new SportsModel;  
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();

        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];


        $user = new WxuserModel;
        $dbResult = $user->where('open_id', $key['openid'])->find();
        if (empty($dbResult)) {
            $this->error("authority error");
        }
        
        $info = [];
        $collegeRankList = $SportsModel -> getCollegeScoreRank();
        $info['score']['update_time'] =  date("Y-m-d H:i",time());
        $info['score']['list'] = $collegeRankList;
        $info['hot']['me'] = $SportsModel -> getStuInfo($key);
        $info['hot']['list'] = $SportsModel -> getCollegeStepsRank();
        $this->success("success",$info);
    }

    /**
     * @param token
     * @param collegeid
     * @type 加密
     */
    public function detail()
    {
        $SportsModel = new SportsModel;
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        if (empty($key['openid']) || empty($key['collegeid'])) {
            $this->error("params error");
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $this->error("authority error");
            }
        }
        
        $returnData = $SportsModel -> getCollegeDetail($key);
        if (!$returnData['status']) {
            $this->error("error",$returnData['data']);
        } 
        $this->success("success",$returnData['data']);
    }
    /**
     * 获取用户捐献步数api
     * @param token
     * @param $key["encryptedData"]
     * @param $key["iv"]
     * @type 加密
     */

    public function steps()
    {
        $key = json_decode(base64_decode($this->request->post('key')),true);
        // $key = $this->request->param();
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }
        $key["openid"] = $userInfo["open_id"];

        $user = new WxuserModel;
        $dbResult = $user->where('open_id', $key['openid'])->find();
        if (empty($dbResult)) {
            $this->error("authority error");
        }
    
        $stu_id = $dbResult['portal_id'];
        //验证是否捐过检验学号以及openid
        $checkResult = $this->checkDonate($stu_id,$key['openid']);
        if ($checkResult['status']) {
            $data = [
                'donate_status' => false,
                'steps'  => $checkResult['data']['steps'],
                'donate_time'  => date("Y-m-d H:i",$checkResult['data']['time']),
            ];
            $this->success("今日已经捐过",$data);
        } 
        $appid = Config::get('wechat.miniapp_chdyiban')["appId"];
        $sessionKey = Db::name('wx_user') -> where('open_id',$key['openid']) -> field('session_key') ->find()['session_key'];
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($key['encryptedData'], $key['iv'], $data );
        if ($errCode == 0) {
            $user = new WxuserModel;
            $data = json_decode($data,true);
            $stepList = $data['stepInfoList'];
            $stepListToday = end($stepList)['step'];
            //步数超过一万步则双倍
            $stepScoreToday = $stepListToday >= 10000 ? 2*$stepListToday : $stepListToday;
            $stuHeat = $this->getStuHeat($stu_id,$stepScoreToday);
            
            //将用户步数放入缓存,存放时间为一天
            Cache::set($stu_id."_steps", $stepScoreToday, 86400);

            $data = [
                'donate_status' => true,
                'steps' => $stepListToday,
                'heat_grow' => $stuHeat,
            ];
            $this->success("success",$data);
        } else {
            $this->error("获取步数失败");
        }

    }

    /**
     * 捐献步数api
     * @param token
     * @type 不加密
     */
    public function donate()
    {
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }
        $userId = $tokenInfo['user_id'];
        $userInfo = WxuserModel::get($userId);
        if (empty($userInfo["portal_id"])) {
            $this->error("请先绑定学号！");
        }

        $stu_id = $userInfo["portal_id"];
        $openid = $userInfo["open_id"];
        $checkResult = $this->checkDonate($stu_id,$openid);
        //再次验证是否捐过
        if ($checkResult['status']) {
            
            $data = [
                'donate_status' => false,
                'steps'  => $checkResult['data']['steps'],
                'donate_time'  => date("Y-m-d H:i",$checkResult['data']['time']),
            ];
            $this->success("今日已经捐过",$data);
        }
        
        if (strlen($stu_id) == 6) {
            $stuInfo = Db::name('teacher_detail') -> where('ID',$stu_id) -> field('YXDM')->find();
        } else {
            $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> field('YXDM')->find();
        }

        $stepListToday = Cache::get($stu_id."_steps");
        //获取缓存是否有该学生步数
        if (empty($stepListToday)) {
            $this->error("请求失败");
        }

        $tempDetail = [
            'YXDM'     =>  $stuInfo['YXDM'],
            'stu_id'   =>  $stu_id,
            'open_id'  =>  $openid,
            'steps'    =>  $stepListToday,
            'time'     =>  time(),
        ];
        $collegeInfo = Db::name('sports_score') 
                    -> where('YXDM',$stuInfo['YXDM'])
                    -> find();
        $newSteps = (int)$collegeInfo['total_steps'] + (int)$stepListToday;
        $newPerson = $collegeInfo['total_person'] + 1;

        $temp = [
            'total_steps'   => $newSteps,
            'total_person'  => $newPerson,
            'average_steps' => number_format($newSteps/self::COLLEGE_PERSON_ARRAY[$stuInfo['YXDM']],4),
        ];
        // 启动事务
        Db::startTrans();     
        $insertFlag = false;
        $updateFlag = false;       
        try{
            $insertFlag = Db::name('sports_steps_detail') -> insert($tempDetail);
            $updateFlag = Db::name('sports_score')->where('YXDM',$stuInfo['YXDM']) -> update($temp);
            // 提交事务
            Db::commit();  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }

        if ($insertFlag && $updateFlag) {
            //删除缓存
            $stepListToday = Cache::rm($stu_id."_steps");
            $data = [
                'donate_status' => true,
            ];
            $this->success("success",$data);
        }
        $this->error("捐献失败，请稍后再试");
    }

    /**
     * @param token
     * 获取日程表
     * @type 不加密
     */
    public function schedule()
    {
        // $key = json_decode(base64_decode($this->request->post('key')),true);
        $key = $this->request->param();
        if (empty($key['token'])) {
            $this->error("access error");
        }
        $token = $key['token'];
        $tokenInfo = Token::get($token);
        if (empty($tokenInfo)) {
            $this->error("Token expired");
        }      
        $SportsModel = new SportsModel;  
        $scheduleList = $SportsModel -> getSchedule();
        $this->success("success",$scheduleList);

    }
    
    // public function insertDate()
    // {
    //     $insertList = Db::name('sports_date_back') -> select();
    //     $timeArray = [
    //         '1' => '2019-04-17',
    //         '2' => '2019-04-17',
    //         '3' => '2019-04-18',
    //         '4' => '2019-04-18',
    //         '5' => '2019-04-19',
    //         '6' => '2019-04-19',
    //     ];
    //     foreach ($insertList as $key => $value) {
    //         $newTime = strtotime($timeArray[$value['sports_day']]." ".$value['sports_time']);
    //         $result = Db::name('sports_date') -> where('id',$value['id']) -> update(['sports_time' => $newTime]);
    //         dump($result);
    //     }
    // }
    /**
     * 判断当天是否已经捐赠过
     * @param int $stu_id
     * @return array 
     */
    private function checkDonate($stu_id,$openid) {
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $donateList = Db::name('sports_steps_detail') 
                        -> where('stu_id',$stu_id)
                        -> where('time','>',$beginToday)
                        -> find();
        $donateOpenidList = Db::name('sports_steps_detail') 
                        -> where('open_id',$openid)
                        -> where('time','>',$beginToday)
                        -> find();
        if (empty($donateList) && empty($donateOpenidList)) {
            //未进行捐赠
            return ['status'=> false,'data' => '' ];
        } else {
            return ['status'=> true,'data' => $donateOpenidList];
        }
    }

    /**
     * 获取学生可为学院贡献多少热度
     * @time 2019/4/11 新增stu_id为教师工号
     */
    private function getStuHeat($stu_id,$stepListToday)
    {
        $SportsModel = new SportsModel;  
        if (strlen($stu_id) == 6) {
            $stuInfo = Db::name('teacher_detail') -> where('ID',$stu_id) -> field('YXDM')->find();
        } else {
            $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> field('YXDM')->find();
        }
        $collegeInfo = Db::name('sports_score') 
                    -> where('YXDM',$stuInfo['YXDM'])
                    -> find();
        $newSteps = (int)$collegeInfo['total_steps'] + (int)$stepListToday;
        $newAverage = number_format($newSteps/self::COLLEGE_PERSON_ARRAY[$stuInfo['YXDM']],4);
        $oldAverage = $collegeInfo['average_steps'];
        $newHeat = $SportsModel -> getHeat($newAverage);
        $oldHeat = $SportsModel -> getHeat($oldAverage);
        return $newHeat - $oldHeat;
    }

}