<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use \think\Cache;
use fast\Http;
use wechat\wxBizDataCrypt;
use app\api\model\Wxuser as WxuserModel;
use app\api\model\Sports as SportsModel;

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
            '2100'  =>  2301,
            //汽车学院
            '2200'  =>  2102,
            //机械学院
            '2500'  =>  2098,
            //经管学院
            '2300'  =>  1873,
            //电控学院
            '3200'  =>  1832,
            //信息学院
            '2400'  =>  2076,
            //地测学院
            '2600'  =>  1786,
            //资源学院
            '2700'  =>  1654,
            //建工学院
            '2800'  =>  2107,
            //环工学院
            '2900'  =>  1897,
            //建筑学院
            '4100'  =>  2011,
            //材料学院
            '3100'  =>  1876,
            //政治学院，马院，文传学院，外语学院，理学院，体育系
            '1100'  =>  1692,
            '6100'  =>  543,
            '3300'  =>  1598,
            '1300'  =>  1520,
            '1200'  =>  1499,
            '1400'  =>  1372,
            //学工部，预科生
            '0001'  =>  1102,

    ];
    public function index()
    {
        date_default_timezone_set('PRC');
        $SportsModel = new SportsModel;  
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        $info = [];
        $collegeRankList = $SportsModel -> getCollegeScoreRank();
        $info['score']['update_time'] =  date("Y-m-d H:i",time());
        $info['score']['list'] = $collegeRankList;
        $info['hot']['me'] = $SportsModel -> getStuInfo($key);
        $info['hot']['list'] = $SportsModel -> getCollegeStepsRank();
        $result = [
            'status' => 200,
            'msg'    => 'success',
            'data'   => $info,
        ];
        return json($result);
    }

    public function detail()
    {
        $SportsModel = new SportsModel;
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid']) || empty($key['collegeid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        }else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        
        $returnData = $SportsModel -> getCollegeDetail($key);
        if (!$returnData['status']) {
            $info = [
                'status' => 200,
                'msg' => 'error',
                'data' => $returnData['data'],
            ];
        } else {
            $info = [
                'status' => 200,
                'msg' => 'success',
                'data' => $returnData['data'],
            ];
        }
        return json($info);

    }
    /**
     * 获取用户捐献步数api
     */

    public function steps()
    {
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        } else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        $stu_id = $dbResult['portal_id'];
        //验证是否捐过检验学号以及openid
        $checkResult = $this->checkDonate($stu_id,$key['openid']);
        if ($checkResult['status']) {
            $info = [
                'status' => 200,
                'msg'  => '今日已经捐过',
                'data' => [
                    'donate_status' => false,
                    'steps'  => $checkResult['data']['steps'],
                    'donate_time'  => date("Y-m-d H:i",$checkResult['data']['time']),
                ],
            ];
            return json($info);
        } 
        $appid = Config::get('wx.appId');
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
           
            $info = [
                'status' => 200,
                'msg'  => 'success',
                'data' => [
                    'donate_status' => true,
                    'steps' => $stepListToday,
                    'heat_grow' => $stuHeat,
                ],
            ];
            //将用户步数放入缓存,存放时间为一天
            Cache::set($stu_id."_steps", $stepScoreToday, 86400);
            return json($info);

        } else {
            $info = [
                'status' => 500,
                'code'   => $errCode,
                'msg'    => '获取步数失败',
            ];
            return json($info);
        }

    }
    //捐献步数api
    public function donate()
    {
        
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        } else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        $stu_id = $dbResult['portal_id'];
        $checkResult = $this->checkDonate($stu_id,$key['openid']);
        //再次验证是否捐过
        if ($checkResult['status']) {
            $info = [
                'status' => 200,
                'msg'  => '今日已经捐过',
                'data' => [
                    'donate_status' => false,
                    'steps'  => $checkResult['data']['steps'],
                    'donate_time'  => date("Y-m-d H:i",$checkResult['data']['time']),
                ],
            ];
            return json($info);
        }
       
        $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> field('YXDM')->find();

        $stepListToday = Cache::get($stu_id."_steps");
        //获取缓存是否有该学生步数
        if (empty($stepListToday)) {
            $info = [
                'status' => 500,
                'msg'    => '非法请求',
                'data'   => [],
            ];

            return json($info);
        }

        $tempDetail = [
            'YXDM'     =>  $stuInfo['YXDM'],
            'stu_id'   =>  $stu_id,
            'open_id'  =>  $key['openid'],
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
            $info = [
                'status' => 200,
                'msg'    => 'success',
                'data'   => [],
            ];
        } else {
            $info = [
                'status' => 500,
                'msg'  => '捐献失败，请稍后再试',
                'data' => [],
            ];
        }
        return json($info);

    }


    public function schedule()
    {
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key['openid'])) {
            $info = [
                'status' => 500,
                'msg' => '参数有误',
            ];
            return json($info);
        } else {
            $user = new WxuserModel;
            $dbResult = $user->where('open_id', $key['openid'])->find();
            if (empty($dbResult)) {
                $info = [
                    'status' => 500,
                    'msg' => 'authority error',
                ];
                return json($info);
            }
        }
        $SportsModel = new SportsModel;  
        $scheduleList = $SportsModel -> getSchedule();
        $info = [
            'status' => 200,
            'msg'    => 'success',
            'data'   => $scheduleList,
        ];
        return json($info);

    }
    // public function insertDate()
    // {
    //     $insertList = Db::name('sports_date_back') -> select();
    //     $timeArray = [
    //         '1' => '2019-04-18',
    //         '2' => '2019-04-18',
    //         '3' => '2019-04-19',
    //         '4' => '2019-04-19',
    //         '5' => '2019-04-20',
    //         '6' => '2019-04-20',
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
     */
    private function getStuHeat($stu_id,$stepListToday)
    {
        $SportsModel = new SportsModel;  
        $stuInfo = Db::name('stu_detail') -> where('XH',$stu_id) -> field('YXDM')->find();
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